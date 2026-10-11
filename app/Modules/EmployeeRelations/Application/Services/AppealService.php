<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseAppeal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Qərara etiraz: işçi (barəsində qərar verilən və ya müraciət edən) Şəxsi kabinetdən
 * `appeal_deadline`-a qədər apellyasiya verir; HR ayrıca yenidən baxış aparır və yekun
 * nəticə verir (qüvvədə saxlanıldı / dəyişdirildi / ləğv edildi).
 *
 * Daxili apellyasiya işçinin məhkəməyə müraciət hüququnu (ƏM Md.190.3) əvəz etmir.
 */
class AppealService
{
    public function __construct(
        private readonly CaseAccess $access,
        private readonly CaseWorkflow $workflow,
        private readonly DecisionService $decisions,
        private readonly CaseNotifier $notifier,
        private readonly CaseAuditLogger $audit,
        private readonly OrderCancellationTracker $orderCancellations,
    ) {}

    /**
     * İşçi bu işə apellyasiya verə bilərmi: rolu (subject/complainant) və ya null.
     */
    public function appellantRole(RelationCase $case, User $user, ?int $personnelId): ?string
    {
        if ($personnelId !== null && $case->subject_personnel_id === $personnelId && $case->decision_notified_at !== null) {
            return RelationCaseAppeal::ROLE_SUBJECT;
        }

        if ($case->reporter_user_id !== null && $case->reporter_user_id === (int) $user->getKey()) {
            return RelationCaseAppeal::ROLE_COMPLAINANT;
        }

        return null;
    }

    public function canAppeal(RelationCase $case, User $user, ?int $personnelId): bool
    {
        return $this->appellantRole($case, $user, $personnelId) !== null
            && $case->status === CaseStatus::Decided
            && $case->appeal_deadline !== null
            && ! $case->appeal_deadline->copy()->endOfDay()->isPast()
            && ! $case->appeals()->where('appellant_user_id', $user->getKey())->exists();
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function submit(RelationCase $case, User $user, ?int $personnelId, string $reason): RelationCaseAppeal
    {
        $role = $this->appellantRole($case, $user, $personnelId);

        if ($role === null) {
            throw new AuthorizationException;
        }

        if (! $this->canAppeal($case, $user, $personnelId)) {
            throw ValidationException::withMessages(['appealReason' => __('employee_relations::cases.messages.appeal_closed')]);
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['appealReason' => __('employee_relations::cases.messages.appeal_reason_short')]);
        }

        $anonymous = $role === RelationCaseAppeal::ROLE_COMPLAINANT && $case->is_anonymous;

        return DB::transaction(function () use ($case, $user, $personnelId, $role, $reason, $anonymous): RelationCaseAppeal {
            $appeal = $case->appeals()->create([
                'appellant_personnel_id' => $personnelId,
                'appellant_user_id' => $user->getKey(),
                'appellant_role' => $role,
                'reason' => $reason,
                'status' => RelationCaseAppeal::STATUS_PENDING,
            ]);

            $this->workflow->move($case, CaseStatus::Appealed, $user, $reason, 'appeal_submitted', $anonymous);
            $this->audit->log('appeal_submitted', $case, ['appeal_id' => $appeal->id, 'role' => $role], $user, $anonymous);

            return $appeal;
        });
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function startReview(RelationCase $case, User $actor, ?string $comment = null): RelationCase
    {
        if (! $this->access->canManage($actor, $case)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($case, $actor, $comment): RelationCase {
            $case->appeals()->where('status', RelationCaseAppeal::STATUS_PENDING)->update(['status' => RelationCaseAppeal::STATUS_UNDER_REVIEW]);

            return $this->workflow->move($case, CaseStatus::AppealReview, $actor, $comment, 'appeal_review_started');
        });
    }

    /**
     * Yenidən baxışın yekunu. «Dəyişdirildi» yeni qərar tələb edir; tənbehə dəyişdirmə
     * eyni müddət yoxlamasından keçir və əmr qaralaması yaradır.
     *
     * «Qərar ləğv edildi» işin nəticəsini «əsassız»a çevirir. Bağlı intizam əmri (və ya
     * başqa tənbehlə əvəz olunan köhnə əmr) avtomatik ləğv OLUNMUR: təsdiqlənmiş sənədin
     * ləğvi Orders modulunun öz axınından keçir. İş «ləğv gözləyir» vəziyyətinə düşür
     * (OrderCancellationTracker), əmr ləğv olunana və ya istisna təsdiqlənənə qədər bağlanmır.
     *
     * @return array{case: RelationCase, order_follow_up: bool, cancellation_pending: bool}
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function resolve(RelationCase $case, User $actor, string $outcome, string $comment, ?DecisionOutcome $newDecision = null): array
    {
        if (! $this->access->canManage($actor, $case)) {
            throw new AuthorizationException;
        }

        if ($case->status !== CaseStatus::AppealReview) {
            throw ValidationException::withMessages(['appealOutcome' => __('employee_relations::cases.messages.transition_not_allowed')]);
        }

        if (! in_array($outcome, RelationCaseAppeal::OUTCOMES, true)) {
            throw ValidationException::withMessages(['appealOutcome' => __('employee_relations::cases.messages.appeal_outcome_invalid')]);
        }

        $comment = trim($comment);

        if ($comment === '') {
            throw ValidationException::withMessages(['appealComment' => __('employee_relations::cases.messages.comment_required')]);
        }

        if ($outcome === RelationCaseAppeal::OUTCOME_MODIFIED && ($newDecision === null || $newDecision === $case->decision_outcome)) {
            throw ValidationException::withMessages(['appealNewDecision' => __('employee_relations::cases.messages.new_decision_required')]);
        }

        $modifiedTo = $outcome === RelationCaseAppeal::OUTCOME_MODIFIED ? $newDecision : null;

        return DB::transaction(function () use ($case, $actor, $outcome, $comment, $newDecision, $modifiedTo): array {
            $hadOrder = $case->order_log_id !== null;
            $previousOrderId = $case->order_log_id !== null ? (int) $case->order_log_id : null;
            $previousOutcome = $case->decision_outcome;

            if ($modifiedTo instanceof DecisionOutcome) {
                $this->decisions->applyOutcome($case, $actor, $modifiedTo, $comment);
            }

            if ($outcome === RelationCaseAppeal::OUTCOME_ANNULLED) {
                // Ləğv edilmiş qərar işin qüvvədə olan nəticəsi deyil: heç bir tədbir görülmür.
                $case->forceFill(['decision_outcome' => DecisionOutcome::Unfounded])->save();
                $this->audit->log('decision_annulled', $case, ['previous_outcome' => $previousOutcome?->value], $actor);
            }

            $appeals = $case->appeals()->where('status', '!=', RelationCaseAppeal::STATUS_RESOLVED)->get();

            foreach ($appeals as $appeal) {
                $appeal->forceFill([
                    'status' => RelationCaseAppeal::STATUS_RESOLVED,
                    'review_outcome' => $outcome,
                    'review_comment' => $comment,
                    'reviewed_by' => $actor->getKey(),
                    'reviewed_at' => now(),
                ])->save();
            }

            $this->workflow->move($case, CaseStatus::Final, $actor, __('employee_relations::cases.appeal_outcomes.'.$outcome).' — '.$comment, 'appeal_resolved');
            $this->audit->log('appeal_resolved', $case, ['outcome' => $outcome, 'new_decision' => $newDecision?->value], $actor);

            foreach ($appeals as $appeal) {
                $this->notifier->appealResolved($case, $appeal->appellant_user_id);
            }

            $orderFollowUp = $hadOrder && (
                $outcome === RelationCaseAppeal::OUTCOME_ANNULLED
                || ($modifiedTo instanceof DecisionOutcome && ! $modifiedTo->isSanction())
            );

            if ($orderFollowUp) {
                $this->workflow->record($case, 'order_follow_up', null, $actor);
            }

            // Ləğv edilməli əmr işin bağlı əmridir. Tənbehin başqa tənbehlə əvəzlənməsi
            // Md.189.1 yoxlamasında (bir xətaya görə bir tənbeh) bloklandığı üçün işin
            // əmri yalnız ləğv və ya tənbehsiz qərarla köhnəlir.
            $orderToCancel = $orderFollowUp ? $previousOrderId : null;

            $cancellationPending = $orderToCancel !== null
                && $this->orderCancellations->request($case, $orderToCancel, $actor);

            return ['case' => $case->refresh(), 'order_follow_up' => $orderFollowUp, 'cancellation_pending' => $cancellationPending];
        });
    }
}
