<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use App\Services\Vacation\VacationBalanceService;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\Carbon;
use DomainException;

/**
 * Recalls the employee from leave (məzuniyyətdən geri çağırma): the leave the employee
 * is on at the recall date ends the day before it, they are back at work on the recall
 * date, and the days not taken go back to the yearly balance when it was annual leave.
 * The leave's original end, return date and length are kept in the order snapshot, so
 * reversal restores the leave and takes the returned days again.
 */
class VacationRecallEffect implements OrderEffect
{
    use RemembersEffectState;

    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly VacationBalanceService $balance,
        private readonly OrderWordTemplateRepository $templates,
    ) {}

    /**
     * @throws DomainException when the employee is not on leave at the recall date
     */
    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $recall = $this->dates->parse($fields['recall_date'] ?? null);

        if (! $recall || blank($personnel->tabel_no)) {
            return;
        }

        $recall = Carbon::parse($recall->format('Y-m-d'));
        $vacation = $this->vacationOn($personnel, $recall);

        if ($vacation === null) {
            throw new DomainException(__('orders::order_composer.errors.recall_no_vacation', ['date' => $recall->format('d.m.Y')]));
        }

        $start = Carbon::parse((string) $vacation->getRawOriginal('start_date'));
        $end = Carbon::parse((string) $vacation->getRawOriginal('end_date'));

        if ($recall->lte($start)) {
            throw new DomainException(__('orders::order_composer.errors.recall_on_first_day'));
        }

        $duration = (int) $vacation->duration;
        $unused = min($duration > 0 ? $duration : PHP_INT_MAX, (int) $recall->diffInDays($end) + 1);
        $annual = $this->isAnnual($vacation);

        $this->rememberState($order, ['vacation_recall' => [
            'vacation_id' => (int) $vacation->id,
            'end_date' => $end->toDateString(),
            'return_work_date' => $vacation->getRawOriginal('return_work_date'),
            'duration' => $duration,
            'returned_days' => $annual ? $unused : 0,
            'balance_year' => (int) $start->year,
        ]]);

        $vacation->forceFill([
            'end_date' => $recall->copy()->subDay()->toDateString(),
            'return_work_date' => $recall->toDateString(),
            'duration' => max(0, $duration - $unused),
        ])->save();

        if ($annual) {
            $this->balance->release($personnel, (int) $start->year, $unused);
        }
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $state = $this->rememberedState($order)['vacation_recall'] ?? null;

        if (! is_array($state)) {
            return;
        }

        PersonnelVacation::query()->whereKey((int) $state['vacation_id'])->get()->each(
            fn (PersonnelVacation $vacation) => $vacation->forceFill([
                'end_date' => $state['end_date'],
                'return_work_date' => $state['return_work_date'],
                'duration' => $state['duration'],
            ])->save()
        );

        if ((int) $state['returned_days'] > 0) {
            $this->balance->consume($personnel, (int) $state['balance_year'], (int) $state['returned_days']);
        }

        $this->forgetState($order, ['vacation_recall']);
    }

    /** The live leave covering the recall date (the latest one, should records overlap). */
    private function vacationOn(Personnel $personnel, Carbon $date): ?PersonnelVacation
    {
        return PersonnelVacation::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Only annual labour leave draws on the yearly balance. A leave issued by an order
     * is annual when that order's type runs the 'vacation' effect; a leave entered by
     * hand (no order behind it) is taken to be annual.
     */
    private function isAnnual(PersonnelVacation $vacation): bool
    {
        $orderNo = $vacation->getAttribute('order_no');

        if (blank($orderNo)) {
            return true;
        }

        $source = OrderLog::query()
            ->where('order_no', $orderNo)
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->whereNotNull('template_snapshot')
            ->orderByDesc('id')
            ->first();

        $code = (string) data_get($source?->template_snapshot, 'template_code', '');
        $template = $code !== '' ? $this->templates->find($code) : null;

        return $template === null || $template->effect === 'vacation';
    }
}
