<?php

namespace App\Modules\Leaves\Application\Services;

use App\Models\LeaveSickCertificate;
use App\Modules\Leaves\Contracts\SickCertificateAttention;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;

/**
 * Open certificates past the alert threshold, in one aggregate query.
 */
class SickCertificateAttentionService implements SickCertificateAttention
{
    public function __construct(private readonly SickCertificateSettings $settings) {}

    public function staleOpen(): array
    {
        $threshold = $this->settings->staleAfterDays();

        if (! InstalledTables::has('leave_sick_certificates')) {
            return ['count' => 0, 'oldest_days' => null, 'threshold_days' => $threshold];
        }

        $today = CarbonImmutable::today();

        $row = LeaveSickCertificate::query()
            ->join('leaves', 'leaves.id', '=', 'leave_sick_certificates.leave_id')
            ->whereNull('leaves.deleted_at')
            ->where('leave_sick_certificates.status', LeaveSickCertificate::STATUS_OPEN)
            ->where('leaves.starts_at', '<', $today->subDays($threshold)->toDateString())
            ->toBase()
            ->selectRaw('COUNT(*) as aggregate, MIN(leaves.starts_at) as oldest')
            ->first();

        $count = (int) ($row->aggregate ?? 0);

        return [
            'count' => $count,
            'oldest_days' => $count > 0 && filled($row->oldest ?? null)
                ? (int) CarbonImmutable::parse(substr((string) $row->oldest, 0, 10))->diffInDays($today)
                : null,
            'threshold_days' => $threshold,
        ];
    }
}
