<?php

namespace App\Modules\Orders\Application\Document;

use App\Models\OrderWordTemplate;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Pure vacation-order rules: which templates carry a day-counted balance, what the
 * author requested (days + the year it counts against), and whether a balance allows it.
 * No persistence — the balance itself comes from the caller.
 */
class OrderVacationRules
{
    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    /**
     * Only day-counted paid leave has a balance to check/deduct. Date-range leaves
     * without a day count (e.g. unpaid leave) are not gated and show no balance.
     */
    public function isDayCounted(OrderWordTemplate $template): bool
    {
        return $template->effect === 'vacation'
            && collect($template->variables ?? [])->contains(fn ($v): bool => ($v['effect_role'] ?? null) === 'days');
    }

    /**
     * The days requested and the year they count against (the start date's year, or
     * the current year when the start date is missing or unparseable).
     *
     * @param  array<string,mixed>  $fields
     * @return array{year:int,requested:int}
     */
    public function request(OrderWordTemplate $template, array $fields): array
    {
        $start = $this->dates->parse($this->effectFieldValue($template, $fields, 'start_date'));

        return [
            'year' => (int) ($start?->year ?? now()->year),
            'requested' => (int) ($this->effectFieldValue($template, $fields, 'days') ?? 0),
        ];
    }

    /**
     * The value the author entered for the template variable carrying $role.
     *
     * @param  array<string,mixed>  $fields
     */
    public function effectFieldValue(OrderWordTemplate $template, array $fields, string $role): ?string
    {
        foreach ($template->variables ?? [] as $variable) {
            if (($variable['effect_role'] ?? null) === $role && ! empty($variable['token'])) {
                $value = $fields[$variable['token']] ?? null;

                return $value === null ? null : (string) $value;
            }
        }

        return null;
    }

    /**
     * Why the balance blocks the order — at least one day, never more than what
     * remains — or null when it may be issued.
     *
     * @param  array{year:int,total:int,used:int,remaining:int,requested:int}  $balance
     */
    public function violation(array $balance): ?string
    {
        if ($balance['requested'] < 1) {
            return __('orders::order_composer.vacation.min_days');
        }

        if ($balance['requested'] > $balance['remaining']) {
            return __('orders::order_composer.vacation.exceeded', [
                'year' => $balance['year'],
                'total' => $balance['total'],
                'used' => $balance['used'],
                'remaining' => $balance['remaining'],
                'requested' => $balance['requested'],
            ]);
        }

        return null;
    }
}
