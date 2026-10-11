<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseAppeal;

/**
 * Şəxsi kabinetin görünüşü:
 *  - «Müraciətlərim» — işçinin özünün göndərdiyi işlər: mərhələ və ümumi nəticə
 *    (əsaslı / əsassız). Görülən tədbirin növü, araşdırıcılar və sübutlar göstərilmir.
 *  - «Barəmdə qərarlar» — işçi barəsində şikayət olunan işdir, amma yalnız qərar ona
 *    bildirildikdən sonra görünür (`decision_notified_at`); şikayətçi və sübutlar görünmür.
 */
class MyCasesReadService
{
    public function __construct(private readonly AppealService $appeals) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function reported(User $user, ?int $personnelId): array
    {
        return RelationCase::query()
            ->with('type:id,name')
            ->where('reporter_user_id', $user->getKey())
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (RelationCase $case): array => [
                'id' => (int) $case->id,
                'number' => $case->label(),
                'type' => (string) $case->type?->name,
                'title' => $case->title,
                'status' => $case->status->label(),
                'status_key' => $case->status->value,
                'anonymous' => $case->is_anonymous,
                'created_at' => $case->created_at?->format('d.m.Y'),
                'result' => $case->decision_outcome === null || ! in_array($case->status, [CaseStatus::Decided, CaseStatus::Appealed, CaseStatus::AppealReview, CaseStatus::Final, CaseStatus::Closed], true)
                    ? null
                    : __('employee_relations::cases.my.results.'.($case->decision_outcome->isSubstantiated() ? 'substantiated' : 'unfounded')),
                'appeal_deadline' => $case->appeal_deadline?->format('d.m.Y'),
                'can_appeal' => $this->appeals->canAppeal($case, $user, $personnelId),
                'appeal' => $this->appealStatus($case, $user),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function aboutMe(User $user, ?int $personnelId): array
    {
        if ($personnelId === null) {
            return [];
        }

        return RelationCase::query()
            ->with('type:id,name')
            ->where('subject_personnel_id', $personnelId)
            ->whereNotNull('decision_notified_at')
            ->latest('decided_at')
            ->limit(100)
            ->get()
            ->map(fn (RelationCase $case): array => [
                'id' => (int) $case->id,
                'number' => $case->label(),
                'type' => (string) $case->type?->name,
                'title' => $case->title,
                'status' => $case->status->label(),
                'outcome' => $case->decision_outcome?->label(),
                'summary' => $case->decision_summary,
                'decided_at' => $case->decided_at?->format('d.m.Y'),
                'appeal_deadline' => $case->appeal_deadline?->format('d.m.Y'),
                'can_appeal' => $this->appeals->canAppeal($case, $user, $personnelId),
                'appeal' => $this->appealStatus($case, $user),
            ])
            ->all();
    }

    /**
     * @return array{status: string, outcome: ?string}|null
     */
    private function appealStatus(RelationCase $case, User $user): ?array
    {
        /** @var RelationCaseAppeal|null $appeal */
        $appeal = $case->appeals()->where('appellant_user_id', $user->getKey())->latest('id')->first();

        if ($appeal === null) {
            return null;
        }

        return [
            'status' => __('employee_relations::cases.appeal_statuses.'.$appeal->status),
            'outcome' => $appeal->review_outcome ? __('employee_relations::cases.appeal_outcomes.'.$appeal->review_outcome) : null,
        ];
    }
}
