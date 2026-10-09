<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Modules\Orders\Infrastructure\Document\Effects\VacationEffect;
use App\Services\Vacation\VacationBalanceService;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Support\Facades\DB;

/**
 * Moves the standard paternity, education and unpaid leave order types off the annual
 * leave effect, and gives back the days their already-approved orders took from the
 * stored yearly balance.
 *
 * ƏM m.112.1 lists four leave kinds — labour leave (əmək məzuniyyəti), social leave,
 * education/creative leave and unpaid leave. Only the labour leave is the yearly
 * balance: paternity leave is social leave (m.125.4), education leave its own kind
 * (m.123), unpaid leave its own kind (m.128–130). These types were seeded with the
 * annual 'vacation' effect, so approving one used to deduct from the balance.
 *
 * Idempotent: only templates still on the 'vacation' effect are processed, and each is
 * switched in the same transaction that restores its orders' days, so a second run (or
 * a later revocation of an old order, which now runs the non-deducting effect) finds
 * nothing to give back.
 */
class NonAnnualLeaveReclassifier
{
    /** Standard order type code => the effect it belongs to under ƏM. */
    public const RECLASSIFIED = [
        'ataliq_mezuniyyeti' => 'social_leave',
        'tehsil_mezuniyyeti' => 'education_leave',
        'odenissiz_mezuniyyet' => 'unpaid_leave',
    ];

    public function __construct(
        private readonly VacationBalanceService $balance,
        private readonly AzerbaijaniDateFormatter $dates,
    ) {}

    /**
     * @return array{templates:int,orders:int,days:int}
     */
    public function run(bool $dryRun = false): array
    {
        $report = ['templates' => 0, 'orders' => 0, 'days' => 0];

        $templates = OrderWordTemplate::query()
            ->whereIn('code', array_keys(self::RECLASSIFIED))
            ->where('effect', 'vacation')
            ->get();

        foreach ($templates as $template) {
            DB::transaction(function () use ($template, $dryRun, &$report): void {
                foreach ($this->approvedOrders($template->code) as $order) {
                    $restored = $this->restore($template, $order, $dryRun);
                    if ($restored > 0) {
                        $report['orders']++;
                        $report['days'] += $restored;
                    }
                }

                if (! $dryRun) {
                    OrderWordTemplate::query()
                        ->whereKey($template->getKey())
                        ->update(['effect' => self::RECLASSIFIED[$template->code]]);
                }

                $report['templates']++;
            });
        }

        return $report;
    }

    /**
     * @return iterable<int, OrderLog>
     */
    private function approvedOrders(string $code): iterable
    {
        return OrderLog::query()
            ->where('template_render_mode', OrderIssueService::RENDER_MODE_DOCX)
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->orderBy('id')
            ->lazy()
            ->filter(fn (OrderLog $order): bool => (((array) $order->template_snapshot)['template_code'] ?? null) === $code);
    }

    /**
     * Give the order's day count back to the stored balance of the year it was taken
     * from. A year with no stored balance row has nothing to give back.
     */
    private function restore(OrderWordTemplate $template, OrderLog $order, bool $dryRun): int
    {
        $snapshot = (array) $order->template_snapshot;
        $fields = $this->roleFields($template, (array) ($snapshot['fields'] ?? []));
        $days = (int) ($fields['days'] ?? 0);
        $personnelId = $snapshot['personnel_id'] ?? null;
        $personnel = $personnelId ? Personnel::find($personnelId) : null;

        if ($days <= 0 || $personnel === null) {
            return 0;
        }

        $start = $this->dates->parse(isset($fields['start_date']) ? (string) $fields['start_date'] : null);
        $year = $start !== null ? (int) $start->year : (int) now()->year;

        if ($this->balance->storedSnapshot($personnel, $year) === null) {
            return 0;
        }

        if (! $dryRun) {
            // The entries the order wrote on approval, or (approved before the work-year
            // ledger) the day count onto the newest work years.
            $this->balance->release($personnel, $year, $days, VacationEffect::sourceKey($order));
        }

        return $days;
    }

    /**
     * @param  array<string,mixed>  $rawFields  token => value
     * @return array<string,mixed> role => value
     */
    private function roleFields(OrderWordTemplate $template, array $rawFields): array
    {
        $fields = [];
        foreach ($template->variables ?? [] as $variable) {
            $role = $variable['effect_role'] ?? null;
            $token = $variable['token'];
            if ($role && $token !== '' && array_key_exists($token, $rawFields)) {
                $fields[$role] = $rawFields[$token];
            }
        }

        return $fields;
    }
}
