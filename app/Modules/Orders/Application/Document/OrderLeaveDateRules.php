<?php

namespace App\Modules\Orders\Application\Document;

use App\Models\OrderWordTemplate;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\CarbonImmutable;

/**
 * Pure date rules for period orders — every template whose variables carry a start and an
 * end date (annual, unpaid, education, paternity, maternity leave, business trip,
 * substitution…):
 *
 *   - the end date is not before the start date;
 *   - the return-to-work date, when the template has one, falls after the end date;
 *   - a day count, when the template has one, is at least 1 and no more than the calendar
 *     span of the period (fewer is allowed: public holidays inside a leave are not counted);
 *   - a work year (İş ili) starts no later than the leave and not before the employee joined.
 *
 * Also fills the dependent dates as the author types (autofill), so the common case needs
 * no arithmetic: dates → day count, start + day count → end date, end date → return date.
 * The same rules run when the order is issued and again when it is approved.
 */
class OrderLeaveDateRules
{
    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    public function applies(OrderWordTemplate $template): bool
    {
        $roles = $this->roleTokens($template);

        return isset($roles['start_date'], $roles['end_date']);
    }

    /**
     * Rule violations keyed by the composer's field error key (fields.<token>).
     *
     * @param  array<string,mixed>  $fields  token => value
     * @return array<string,string>
     */
    public function violations(OrderWordTemplate $template, array $fields, ?CarbonImmutable $joinDate = null): array
    {
        if (! $this->applies($template)) {
            return [];
        }

        $roles = $this->roleTokens($template);
        $errors = [];

        $start = $this->dateFor($fields, $roles['start_date']);
        $end = $this->dateFor($fields, $roles['end_date']);

        foreach (['start_date' => $start, 'end_date' => $end] as $role => $date) {
            if ($date === null && filled($fields[$roles[$role]] ?? null)) {
                $errors['fields.'.$roles[$role]] = __('orders::order_composer.errors.leave_dates.invalid_date');
            }
        }

        if ($start && $end && $end->lt($start)) {
            $errors['fields.'.$roles['end_date']] = __('orders::order_composer.errors.leave_dates.end_before_start');
        }

        if (isset($roles['return_date']) && $end && filled($fields[$roles['return_date']] ?? null)) {
            $return = $this->dateFor($fields, $roles['return_date']);
            if ($return === null) {
                $errors['fields.'.$roles['return_date']] = __('orders::order_composer.errors.leave_dates.invalid_date');
            } elseif ($return->lte($end)) {
                $errors['fields.'.$roles['return_date']] = __('orders::order_composer.errors.leave_dates.return_not_after_end');
            }
        }

        if (isset($roles['days']) && filled($fields[$roles['days']] ?? null)) {
            $days = (int) $fields[$roles['days']];
            $span = ($start && $end && $end->gte($start)) ? (int) $start->diffInDays($end) + 1 : null;

            if ($days < 1) {
                $errors['fields.'.$roles['days']] = __('orders::order_composer.errors.leave_dates.days_min');
            } elseif ($span !== null && $days > $span) {
                $errors['fields.'.$roles['days']] = __('orders::order_composer.errors.leave_dates.days_exceed_span', ['days' => $days, 'span' => $span]);
            }
        }

        foreach ($this->workYearTokens($template) as $token) {
            $workYear = $this->dateFor($fields, $token);
            if ($workYear === null) {
                continue;
            }

            if ($start && $workYear->gt($start)) {
                $errors['fields.'.$token] = __('orders::order_composer.errors.leave_dates.work_year_after_start');
            } elseif ($joinDate && $workYear->lt($joinDate->startOfDay())) {
                $errors['fields.'.$token] = __('orders::order_composer.errors.leave_dates.work_year_before_join', ['date' => $joinDate->format('d.m.Y')]);
            }
        }

        return $errors;
    }

    /**
     * The period the order puts the employee away for, or null when the template has no
     * period or its dates are incomplete/inverted.
     *
     * @param  array<string,mixed>  $fields
     * @return array{0:CarbonImmutable,1:CarbonImmutable}|null
     */
    public function period(OrderWordTemplate $template, array $fields): ?array
    {
        if (! $this->applies($template)) {
            return null;
        }

        $roles = $this->roleTokens($template);
        $start = $this->dateFor($fields, $roles['start_date']);
        $end = $this->dateFor($fields, $roles['end_date']);

        return $start && $end && $end->gte($start) ? [$start, $end] : null;
    }

    /**
     * Fill the dates that follow from the one the author just changed. Only empty or
     * previously derived values are overwritten in spirit: a changed start/end recomputes
     * the day count, a changed day count recomputes the end date, and a new end date
     * moves the return date to the next day.
     *
     * @param  array<string,mixed>  $fields
     * @return array<string,mixed>
     */
    public function autofill(OrderWordTemplate $template, array $fields, string $changedToken): array
    {
        if (! $this->applies($template)) {
            return $fields;
        }

        $roles = $this->roleTokens($template);
        $start = $this->dateFor($fields, $roles['start_date']);
        $daysToken = $roles['days'] ?? null;
        $endChanged = false;

        if ($changedToken === $daysToken || ($changedToken === $roles['start_date'] && $daysToken && filled($fields[$daysToken] ?? null) && blank($fields[$roles['end_date']] ?? null))) {
            $days = (int) ($fields[$daysToken] ?? 0);
            if ($start && $days >= 1) {
                $fields[$roles['end_date']] = $start->addDays($days - 1)->format('Y-m-d');
                $endChanged = true;
            }
        } elseif (in_array($changedToken, [$roles['start_date'], $roles['end_date']], true)) {
            $end = $this->dateFor($fields, $roles['end_date']);
            if ($daysToken && $start && $end && $end->gte($start)) {
                $fields[$daysToken] = (string) ((int) $start->diffInDays($end) + 1);
            }
            $endChanged = $changedToken === $roles['end_date'];
        }

        if ($endChanged && isset($roles['return_date'])) {
            $end = $this->dateFor($fields, $roles['end_date']);
            if ($end) {
                $fields[$roles['return_date']] = $end->addDay()->format('Y-m-d');
            }
        }

        return $fields;
    }

    /**
     * @return array<string,string> effect role => field token
     */
    private function roleTokens(OrderWordTemplate $template): array
    {
        $roles = [];
        foreach ($template->variables ?? [] as $variable) {
            $role = $variable['effect_role'] ?? null;
            $token = $variable['field']['key'] ?? $variable['token'] ?? null;
            if (($variable['source'] ?? 'manual') === 'manual' && $role && $token && ! isset($roles[$role])) {
                $roles[$role] = (string) $token;
            }
        }

        return $roles;
    }

    /**
     * @return list<string>
     */
    private function workYearTokens(OrderWordTemplate $template): array
    {
        return collect($template->variables ?? [])
            ->filter(fn (array $variable): bool => ($variable['field']['type'] ?? null) === 'work_year' && ! empty($variable['field']['key']))
            ->map(fn (array $variable): string => (string) $variable['field']['key'])
            ->values()
            ->all();
    }

    /**
     * @param  array<string,mixed>  $fields
     */
    private function dateFor(array $fields, string $token): ?CarbonImmutable
    {
        $raw = $fields[$token] ?? null;

        if (blank($raw)) {
            return null;
        }

        $date = $this->dates->parse((string) $raw);

        return $date ? CarbonImmutable::instance($date)->startOfDay() : null;
    }
}
