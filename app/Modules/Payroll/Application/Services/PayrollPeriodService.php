<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollPeriodService
{
    /** Shortest reason accepted for reopening a closed pay month. */
    public const MIN_REASON_LENGTH = 5;

    /** Permission that reopens a closed pay month. */
    public const REOPEN_PERMISSION = 'reopen-payroll-period';

    /**
     * Create the month's period, or return the existing one untouched. Creating never
     * changes an existing period's status: a closed month is reopened only by reopen().
     *
     * @param  array<string,mixed>  $extra
     */
    public function createPeriod(int $year, int $month, array $extra = []): PayrollPeriod
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return PayrollPeriod::firstOrCreate(
            ['code' => sprintf('%04d-%02d', $year, $month)],
            [
                'year' => $year,
                'month' => $month,
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
                'currency' => $extra['currency'] ?? 'AZN',
                'status' => $extra['status'] ?? 'open',
                'note' => $extra['note'] ?? null,
            ],
        );
    }

    public function close(PayrollPeriod $period): PayrollPeriod
    {
        $period->update(['status' => 'closed']);

        return $period;
    }

    /**
     * Reopen a closed pay month: a separate permission, a written reason and an audit entry,
     * because everything computed from the month becomes changeable again.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function reopen(PayrollPeriod $period, string $reason, ?User $actor = null): PayrollPeriod
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User || ! $actor->can(self::REOPEN_PERMISSION)) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw ValidationException::withMessages(['reopenReason' => __('payroll::dashboard.messages.reopen_reason_required', ['min' => self::MIN_REASON_LENGTH])]);
        }

        return DB::transaction(function () use ($period, $reason, $actor): PayrollPeriod {
            $locked = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isClosed()) {
                throw ValidationException::withMessages(['reopenReason' => __('payroll::dashboard.messages.period_not_closed')]);
            }

            $locked->update(['status' => 'open']);

            activity('payroll_period')
                ->performedOn($locked)
                ->causedBy($actor)
                ->event('reopened')
                ->withProperties(['code' => $locked->code, 'reason' => mb_substr($reason, 0, 500)])
                ->log('payroll.period.reopened');

            $period->setRawAttributes($locked->getAttributes(), true);

            return $period;
        });
    }

    /**
     * Delete a period only while nothing in it has been approved or paid: a closed period,
     * or one with an approved or locked run, would take payslips, loan repayments and the
     * retro ledger with it (cascade) without restoring the loans.
     *
     * @throws ValidationException
     */
    public function delete(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $locked = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }

            if ($locked->isClosed() || $locked->runs()->whereIn('status', ['approved', 'locked'])->exists()) {
                throw ValidationException::withMessages(['period' => __('payroll::dashboard.messages.period_delete_blocked')]);
            }

            $locked->delete();
        });
    }
}
