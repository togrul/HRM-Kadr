<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Compensation\Contracts\OrderCompensationSync;
use App\Support\Language\AzerbaijaniDateFormatter;
use DomainException;
use Illuminate\Support\Carbon;

/**
 * Changes the employee's salary: through the Compensation contract a new active
 * compensation with the order's amount takes effect from the order's effective date
 * (the current one ends the day before). What it ended is kept in the order snapshot,
 * so reversal removes the new compensation and puts the previous one back.
 */
class SalaryChangeEffect implements OrderEffect
{
    use RemembersEffectState;

    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly OrderCompensationSync $compensation,
    ) {}

    /**
     * @throws DomainException when the amount is not a positive number
     */
    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $amount = AwardEffect::parseAmount((string) ($fields['new_salary'] ?? ''));

        if ($amount === null || blank($personnel->tabel_no)) {
            return;
        }

        if ($amount <= 0) {
            throw new DomainException(__('orders::order_composer.errors.salary_amount_invalid', ['amount' => (string) $fields['new_salary']]));
        }

        $from = $this->dates->parse($fields['effective_date'] ?? null)
            ?? ($order->given_date ? Carbon::parse($order->given_date) : today());

        $state = $this->compensation->changeSalaryFromOrder(
            (string) $personnel->tabel_no,
            $amount,
            Carbon::parse($from->format('Y-m-d')),
            $order->order_no,
        );

        if ($state !== null) {
            $this->rememberState($order, ['salary_change' => $state]);
        }
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $state = $this->rememberedState($order)['salary_change'] ?? null;

        if (is_array($state)) {
            $this->compensation->revertSalaryChange($state);
        }

        $this->forgetState($order, ['salary_change']);
    }
}
