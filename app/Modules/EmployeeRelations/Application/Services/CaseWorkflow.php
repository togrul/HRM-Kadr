<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Status keçidləri — yalnız serverdə və yalnız icazəli qrafa görə (CaseStatus::graph()).
 * Hər keçid tarixçəyə (kim, nə vaxt, şərh) və audit jurnalına yazılır; yeni mərhələnin
 * SLA son tarixi qoyulur, gecikmiş mərhələdən çıxış SLA pozuntusu kimi sayılır.
 */
class CaseWorkflow
{
    public function __construct(
        private readonly CaseAccess $access,
        private readonly CaseSla $sla,
        private readonly CaseAuditLogger $audit,
    ) {}

    /**
     * «Mərhələni dəyiş» əməliyyatı: yalnız manualTargets() keçidləri.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transition(RelationCase $case, CaseStatus $target, User $actor, ?string $comment = null): RelationCase
    {
        $current = $case->status;

        if (! in_array($target, $current->manualTargets(), true)) {
            throw ValidationException::withMessages(['transition' => __('employee_relations::cases.messages.transition_not_allowed')]);
        }

        $allowed = match ($target) {
            CaseStatus::Investigating => $this->access->canWork($actor, $case),
            default => $this->access->canManage($actor, $case),
        };

        if (! $allowed) {
            throw new AuthorizationException;
        }

        $comment = trim((string) $comment);

        if ($target === CaseStatus::Closed && $comment === '') {
            throw ValidationException::withMessages(['comment' => __('employee_relations::cases.messages.comment_required')]);
        }

        if ($current === CaseStatus::Decided && $target === CaseStatus::Closed) {
            $this->guardAppealWindowClosed($case);
        }

        return $this->move($case, $target, $actor, $comment, 'transition');
    }

    /**
     * Qrafa uyğun keçidi tətbiq edir. Xidmətlər (qərar, apellyasiya) bunu öz icazə
     * yoxlamasından sonra çağırır.
     *
     * @throws ValidationException
     */
    public function move(RelationCase $case, CaseStatus $target, ?User $actor, ?string $comment, string $action, bool $anonymousActor = false): RelationCase
    {
        return DB::transaction(function () use ($case, $target, $actor, $comment, $action, $anonymousActor): RelationCase {
            /** @var RelationCase $locked */
            $locked = RelationCase::query()->with('type')->lockForUpdate()->findOrFail($case->getKey());
            $from = $locked->status;

            if (! $from->canMoveTo($target)) {
                throw ValidationException::withMessages(['transition' => __('employee_relations::cases.messages.transition_not_allowed')]);
            }

            $now = now();
            $breached = $locked->isOverdue();

            $locked->forceFill([
                'status' => $target,
                'stage_entered_at' => $now,
                'stage_due_at' => $this->sla->dueAt($locked, $target, $now),
                'sla_breach_count' => $locked->sla_breach_count + ($breached ? 1 : 0),
                'last_reminder_at' => null,
                'closed_at' => $target === CaseStatus::Closed ? $now : $locked->closed_at,
            ])->save();

            $this->record($locked, $action, $comment, $anonymousActor ? null : $actor, $from->value, $target->value);
            $this->audit->log('status_changed', $locked, [
                'from' => $from->value,
                'to' => $target->value,
                'sla_breached' => $breached,
            ], $actor, $anonymousActor);

            $case->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    public function record(RelationCase $case, string $action, ?string $comment, ?User $actor, ?string $from = null, ?string $to = null): RelationCaseEvent
    {
        return RelationCaseEvent::query()->create([
            'case_id' => $case->getKey(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => filled($comment) ? $comment : null,
            'actor_user_id' => $actor?->getKey(),
            'created_at' => now(),
        ]);
    }

    /**
     * Qərar verilmiş iş apellyasiya müddəti bitənə və açıq apellyasiya qalmayana qədər
     * bağlanmır — əks halda işçinin etiraz hüququ yan keçilərdi.
     *
     * @throws ValidationException
     */
    private function guardAppealWindowClosed(RelationCase $case): void
    {
        $deadline = $case->appeal_deadline;

        if ($deadline !== null && $deadline->copy()->endOfDay()->isFuture()) {
            throw ValidationException::withMessages(['transition' => __('employee_relations::cases.messages.appeal_window_open', [
                'date' => $deadline->format('d.m.Y'),
            ])]);
        }

        if ($case->appeals()->where('status', '!=', 'resolved')->exists()) {
            throw ValidationException::withMessages(['transition' => __('employee_relations::cases.messages.appeal_pending')]);
        }
    }
}
