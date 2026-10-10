<?php

namespace App\Modules\Leaves\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Payroll\Contracts\ClosedPeriodCheck;
use App\Services\Absence\AbsenceOverlapException;
use App\Services\Absence\AbsenceOverlapGuard;
use App\Services\UserPersonnelLinkResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Creates and edits leaves recorded by HR on an employee's behalf, holding the rules the
 * form alone cannot be trusted with:
 *
 *   - a leave starts as "awaiting approval" and goes through its approval route; only a
 *     user holding `approve-leaves` may record it as already approved, and then it is
 *     stamped as approved by that user (with a status-log entry for the audit trail);
 *   - nobody approves their own leave — the approver cannot be the employee;
 *   - the employee cannot be away twice: a leave may not overlap a live leave, vacation
 *     or business trip (AbsenceOverlapGuard).
 *   - a leave written by an order (submission_source = order) belongs to that order: it is
 *     changed or removed only by revoking the order, like a certificate leave;
 *   - changing the dates of an approved leave sends it back for approval, unless the actor
 *     may approve leaves — then it is re-stamped as approved by them;
 *   - a leave touching a month closed for pay (payroll/finance/attendance) is not created,
 *     changed or deleted without `edit-closed-month-leaves`.
 *
 * Violations surface as a ValidationException keyed by the payload field.
 */
class LeaveRecordService
{
    public const APPROVE_PERMISSION = 'approve-leaves';

    /** Lets HR correct leaves in a month already closed for pay. */
    public const CLOSED_MONTH_PERMISSION = 'edit-closed-month-leaves';

    /** The columns that define when the employee is away; changing one re-opens approval. */
    private const PERIOD_FIELDS = ['tabel_no', 'leave_type_id', 'starts_at', 'ends_at', 'duration_unit', 'partial_day_part', 'starts_time', 'ends_time'];

    public function __construct(private readonly AbsenceOverlapGuard $absences) {}

    /**
     * Statuses the actor may give a leave they record or edit: "awaiting approval"
     * always, "approved" only with the approve permission, and whatever the edited
     * leave already has (keeping it is not a decision).
     *
     * @return list<int>
     */
    public function allowedStatusIds(?User $actor, ?Leave $existing = null): array
    {
        $allowed = [OrderStatusEnum::PENDING->value];

        if ($this->canRecordApproved($actor)) {
            $allowed[] = OrderStatusEnum::APPROVED->value;
        }

        if ($existing !== null && $existing->status_id !== null) {
            $allowed[] = (int) $existing->status_id;
        }

        return array_values(array_unique($allowed));
    }

    public function canRecordApproved(?User $actor): bool
    {
        return (bool) $actor?->can(self::APPROVE_PERMISSION);
    }

    /**
     * @param  array<string, mixed>  $payload  LeaveForm::toPayload()
     *
     * @throws ValidationException
     */
    public function create(array $payload, User $actor): Leave
    {
        $this->assertValid($payload, $actor);
        $this->assertMonthsOpen($actor, $payload);

        return DB::transaction(function () use ($payload, $actor): Leave {
            $leave = Leave::query()->create($this->withApprovalStamp($payload, $actor, null));
            $this->logRecordedApproval($leave, $actor, null);

            return $leave;
        });
    }

    /**
     * @param  array<string, mixed>  $payload  LeaveForm::toPayload()
     *
     * @throws ValidationException
     */
    public function update(Leave $leave, array $payload, User $actor): Leave
    {
        $this->assertEditable($leave);

        $this->assertValid($payload, $actor, $leave);
        $this->assertMonthsOpen($actor, $payload, $leave);

        return DB::transaction(function () use ($leave, $payload, $actor): Leave {
            $previousStatus = (int) $leave->status_id;
            $restamp = $this->periodChanged($leave, $payload);
            $leave->update($this->withApprovalStamp($payload, $actor, $leave, $restamp));
            $this->logRecordedApproval($leave, $actor, $restamp ? null : $previousStatus);

            return $leave;
        });
    }

    /**
     * Delete a leave recorded in HR. A leave of a sick certificate or of an order is removed
     * where it came from; one in a month closed for pay needs the closed-month permission.
     *
     * @throws ValidationException
     */
    public function delete(Leave $leave, User $actor): void
    {
        $this->assertEditable($leave);
        $this->assertMonthsOpen($actor, [], $leave);

        $leave->delete();
    }

    /**
     * @throws ValidationException
     */
    public function assertEditable(Leave $leave): void
    {
        if ($leave->isManagedBySickCertificate()) {
            throw ValidationException::withMessages(['starts_at' => __('leaves::common.validation.managed_by_sick_certificate')]);
        }

        if ($leave->isManagedByOrder()) {
            throw ValidationException::withMessages(['starts_at' => __('leaves::common.validation.managed_by_order')]);
        }
    }

    /**
     * Every month the new payload and the existing leave touch must be open for pay, unless
     * the actor holds the closed-month permission.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function assertMonthsOpen(?User $actor, array $payload, ?Leave $existing = null): void
    {
        if ((bool) $actor?->can(self::CLOSED_MONTH_PERMISSION) || ! app()->bound(ClosedPeriodCheck::class)) {
            return;
        }

        $months = [];
        foreach ([[$payload['starts_at'] ?? null, $payload['ends_at'] ?? null], [$existing?->getRawOriginal('starts_at'), $existing?->getRawOriginal('ends_at')]] as [$from, $to]) {
            $start = $this->date($from);
            if ($start === null) {
                continue;
            }

            $end = $this->date($to) ?? $start;
            for ($month = $start->startOfMonth(); $month->lte($end) && count($months) < 600; $month = $month->addMonth()) {
                $months[$month->format('Y-m')] = $month;
            }
        }

        $closed = app(ClosedPeriodCheck::class);
        foreach ($months as $month) {
            if ($closed->closedBy($month) !== null) {
                throw ValidationException::withMessages(['starts_at' => __('leaves::common.validation.month_closed', ['period' => $month->format('m.Y')])]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function periodChanged(Leave $leave, array $payload): bool
    {
        foreach (self::PERIOD_FIELDS as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $before = $leave->getRawOriginal($field);
            $after = $payload[$field];

            if (in_array($field, ['starts_at', 'ends_at'], true)) {
                $before = $this->date($before)?->toDateString();
                $after = $this->date($after)?->toDateString();
            }

            if ((string) $before !== (string) $after) {
                return true;
            }
        }

        return false;
    }

    /**
     * Defensive check for the approval flows: a pending leave that would now overlap
     * another live absence must not be approved.
     *
     * @throws AbsenceOverlapException
     */
    public function assertApprovable(Leave $leave): void
    {
        $period = $leave->absencePeriod();

        if ($period !== null && filled($leave->tabel_no)) {
            $this->absences->assertNoOverlap((string) $leave->tabel_no, $period);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function assertValid(array $payload, ?User $actor, ?Leave $existing = null): void
    {
        $errors = $this->violations($payload, $actor, $existing);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string> payload field => message
     */
    public function violations(array $payload, ?User $actor, ?Leave $existing = null): array
    {
        $errors = [];

        $statusId = isset($payload['status_id']) ? (int) $payload['status_id'] : null;
        if ($statusId === null || ! in_array($statusId, $this->allowedStatusIds($actor, $existing), true)) {
            $errors['status_id'] = __('leaves::common.validation.status_not_allowed');
        }

        $tabelNo = (string) ($payload['tabel_no'] ?? '');
        $employeeId = $tabelNo !== '' ? (int) Personnel::query()->where('tabel_no', $tabelNo)->value('id') : 0;
        $approverId = isset($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
        if ($approverId && $employeeId > 0 && $approverId === $employeeId) {
            $errors['assigned_to.id'] = __('leaves::common.validation.self_approver');
        }

        // Nobody records their own leave as approved, whoever the assigned approver is.
        if ($statusId === OrderStatusEnum::APPROVED->value && $actor !== null && $employeeId > 0
            && ! ($existing !== null && (int) $existing->status_id === OrderStatusEnum::APPROVED->value && ! $this->periodChanged($existing, $payload))
            && app(UserPersonnelLinkResolver::class)->resolve($actor) === $employeeId) {
            $errors['status_id'] = __('leaves::common.validation.self_approval');
        }

        $start = $this->date($payload['starts_at'] ?? null);
        $end = $this->date($payload['ends_at'] ?? null) ?? $start;
        if ($start && $end && $end->lt($start)) {
            $errors['ends_at'] = __('leaves::common.validation.end_before_start');
        }

        if ($start && $end && $tabelNo !== '' && ! isset($errors['ends_at'])) {
            $candidate = (new Leave)->forceFill([
                'starts_at' => $start->toDateString(),
                'ends_at' => $end->toDateString(),
                'duration_unit' => $payload['duration_unit'] ?? 'day',
                'partial_day_part' => $payload['partial_day_part'] ?? null,
                'starts_time' => $payload['starts_time'] ?? null,
                'ends_time' => $payload['ends_time'] ?? null,
            ]);

            if ($existing !== null) {
                $candidate->setAttribute('id', $existing->getKey());
                $candidate->exists = true;
            }

            $period = $candidate->absencePeriod();
            $overlap = $period ? $this->absences->violation($tabelNo, $period) : null;
            if ($overlap !== null) {
                $errors['starts_at'] = $overlap;
            }
        }

        return $errors;
    }

    /**
     * A leave recorded straight as approved carries who approved it and when; any other
     * status carries no approval stamp.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withApprovalStamp(array $payload, User $actor, ?Leave $existing, bool $periodChanged = false): array
    {
        $statusId = (int) ($payload['status_id'] ?? 0);

        // New dates on an approved leave were never approved: back to the approval route,
        // unless the actor may approve — then the new dates carry their stamp.
        if ($statusId === OrderStatusEnum::APPROVED->value && $periodChanged && ! $this->canRecordApproved($actor)) {
            $statusId = OrderStatusEnum::PENDING->value;
            $payload['status_id'] = $statusId;
        }

        if ($statusId !== OrderStatusEnum::APPROVED->value) {
            return [...$payload, 'approved_at' => null, 'approved_by' => null];
        }

        // Editing an already approved leave without touching its dates keeps its approval.
        if (! $periodChanged && $existing !== null && (int) $existing->status_id === OrderStatusEnum::APPROVED->value) {
            return $payload;
        }

        return [...$payload, 'approved_at' => now(), 'approved_by' => $this->actorKey($actor)];
    }

    /** Leave the status-log trace a reviewer's approval would have left. */
    private function logRecordedApproval(Leave $leave, User $actor, ?int $previousStatus): void
    {
        if ((int) $leave->status_id !== OrderStatusEnum::APPROVED->value || $previousStatus === OrderStatusEnum::APPROVED->value) {
            return;
        }

        $leave->logs()->create([
            'status_id' => OrderStatusEnum::APPROVED->value,
            'changed_by' => $this->actorKey($actor),
            'comment' => __('leaves::common.messages.recorded_as_approved'),
            'changed_at' => now(),
        ]);
    }

    /**
     * Təsdiqçi sütunları əməkdaş kartına işarə edir; kart yalnız açıq bağdan götürülür.
     * Bağı olmayan istifadəçi üçün null yazılır — istifadəçi id-si əməkdaş id-si kimi saxlanmır.
     */
    private function actorKey(User $actor): ?int
    {
        return app(UserPersonnelLinkResolver::class)->resolve($actor);
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
