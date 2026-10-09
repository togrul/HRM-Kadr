<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Compensation\Contracts\OrderCompensationSync;
use App\Support\Language\AzerbaijaniDateFormatter;
use DomainException;

/**
 * Entrusts the employee with an absent colleague's duties on top of their own
 * (əvəzetmə): the substitution — whom they stand in for, the position, the period and
 * the extra pay (a percent of salary or a fixed amount) — goes on record in the
 * Compensation module's substitution register through its contract. Reversal removes
 * exactly that record.
 */
class SubstitutionEffect implements OrderEffect
{
    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly OrderCompensationSync $compensation,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $start = $this->dates->parse($fields['start_date'] ?? null);

        // Fail safe: a substitution without its first day is not put on record.
        if (! $start || blank($personnel->tabel_no)) {
            return;
        }

        $end = $this->dates->parse($fields['end_date'] ?? null);
        [$substitutedTabelNo, $substitutedName] = $this->substituted($fields['substituted_employee'] ?? null);

        $this->compensation->recordSubstitution((string) $personnel->tabel_no, [
            'substituted_tabel_no' => $substitutedTabelNo,
            'substituted_name' => $substitutedName,
            'substituted_position_id' => is_numeric($fields['substituted_position'] ?? null) ? (int) $fields['substituted_position'] : null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end?->format('Y-m-d'),
            'extra_pay_percent' => $this->number($fields['extra_pay_percent'] ?? null),
            'extra_pay_amount' => $this->number($fields['extra_pay_amount'] ?? null),
            'order_no' => $order->order_no,
        ], self::sourceKey($order));
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $this->compensation->removeSubstitution(self::sourceKey($order));
    }

    public static function sourceKey(OrderLog $order): string
    {
        return 'order_substitution:'.$order->id;
    }

    /**
     * The substituted colleague: a personnel-list pick submits the employee id; older
     * templates hold the name as typed.
     *
     * @return array{0:?string,1:?string}
     */
    private function substituted(mixed $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return [null, null];
        }

        $colleague = ctype_digit($raw) ? Personnel::query()->find((int) $raw, ['id', 'tabel_no', 'surname', 'name', 'patronymic']) : null;

        if ($colleague === null) {
            return [null, $raw];
        }

        return [(string) $colleague->tabel_no, trim($colleague->surname.' '.$colleague->name.' '.$colleague->patronymic)];
    }

    /**
     * A typed pay figure ("15", "15%", "250,50 manat") as a number; anything that does
     * not read as one is left out rather than blocking the approval.
     */
    private function number(mixed $value): ?float
    {
        $digits = trim((string) preg_replace('/[^\d.,\s\x{00A0}]/u', '', (string) $value), " .,\u{00A0}");

        try {
            return AwardEffect::parseAmount($digits);
        } catch (DomainException) {
            return null;
        }
    }
}
