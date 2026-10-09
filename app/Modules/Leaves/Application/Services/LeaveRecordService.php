<?php

namespace App\Modules\Leaves\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\User;
use App\Services\Absence\AbsenceOverlapException;
use App\Services\Absence\AbsenceOverlapGuard;
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
 *
 * Violations surface as a ValidationException keyed by the payload field.
 */
class LeaveRecordService
{
    public const APPROVE_PERMISSION = 'approve-leaves';

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
        if ($leave->isManagedBySickCertificate()) {
            throw ValidationException::withMessages(['starts_at' => __('leaves::common.validation.managed_by_sick_certificate')]);
        }

        $this->assertValid($payload, $actor, $leave);

        return DB::transaction(function () use ($leave, $payload, $actor): Leave {
            $previousStatus = (int) $leave->status_id;
            $leave->update($this->withApprovalStamp($payload, $actor, $leave));
            $this->logRecordedApproval($leave, $actor, $previousStatus);

            return $leave;
        });
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
        $approverId = isset($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
        if ($approverId && $tabelNo !== '' && $approverId === (int) Personnel::query()->where('tabel_no', $tabelNo)->value('id')) {
            $errors['assigned_to.id'] = __('leaves::common.validation.self_approver');
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
    private function withApprovalStamp(array $payload, User $actor, ?Leave $existing): array
    {
        $statusId = (int) ($payload['status_id'] ?? 0);

        if ($statusId !== OrderStatusEnum::APPROVED->value) {
            return [...$payload, 'approved_at' => null, 'approved_by' => null];
        }

        // Editing an already approved leave keeps its original approval.
        if ($existing !== null && (int) $existing->status_id === OrderStatusEnum::APPROVED->value) {
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

    /** Approver columns point at personnel; a user without a personnel card is kept by user id (as the review flow does). */
    private function actorKey(User $actor): int
    {
        return (int) ($actor->personnel?->id ?: $actor->getKey());
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
