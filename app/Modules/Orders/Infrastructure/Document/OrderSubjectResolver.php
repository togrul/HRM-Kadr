<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\Candidate;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Orders\Application\Document\OrderComposition;

/**
 * Who an order is about: validates that the composer picked the subject a template
 * needs, and builds the Personnel the document's employee.* variables resolve from.
 */
class OrderSubjectResolver
{
    /**
     * The subject's missing-selection errors (error bag key => message); empty when valid.
     * A hire needs a candidate + target structure/position; other types need an employee
     * only when the template maps an employee.* variable.
     *
     * @return array<string,string>
     */
    public function subjectErrors(OrderWordTemplate $template, OrderComposition $composition): array
    {
        if ($template->isHire()) {
            if (! $composition->candidateId) {
                return ['candidateId' => __('orders::order_composer.errors.candidate_required')];
            }
            if (! $composition->hireStructureId || ! $composition->hirePositionId) {
                return ['hirePositionId' => __('orders::order_composer.errors.hire_target_required')];
            }

            return [];
        }

        $needsEmployee = collect($template->variables ?? [])
            ->contains(fn ($v): bool => ($v['source'] ?? '') === 'auto' && str_starts_with((string) ($v['auto_key'] ?? ''), 'employee.'));

        if ($needsEmployee && ! $composition->personnelId) {
            return ['personnelId' => __('orders::order_composer.errors.personnel_required')];
        }

        return [];
    }

    /**
     * The person the document's employee.* variables resolve from: the picked employee,
     * or (for hire) a transient employee built from the candidate + the structure/
     * position they are hired into — so names/structure/position decline correctly.
     */
    public function subject(OrderWordTemplate $template, OrderComposition $composition): ?Personnel
    {
        if (! $template->isHire()) {
            return $this->personnel($composition->personnelId);
        }

        $candidate = $composition->candidateId ? Candidate::find($composition->candidateId) : null;
        if (! $candidate) {
            return null;
        }

        $pseudo = new Personnel;
        $pseudo->surname = $candidate->surname;
        $pseudo->name = $candidate->name;
        $pseudo->patronymic = $candidate->patronymic;
        $pseudo->gender = $candidate->gender;
        $pseudo->structure_id = $composition->hireStructureId;
        $pseudo->setRelation('structure', $composition->hireStructureId ? Structure::find($composition->hireStructureId) : null);
        $pseudo->setRelation('position', $composition->hirePositionId ? Position::find($composition->hirePositionId) : null);

        return $pseudo;
    }

    /**
     * The picked employee with the structure/position the document declines.
     */
    public function personnel(?int $personnelId): ?Personnel
    {
        return $personnelId
            ? Personnel::with(['structure:id,name', 'position:id,name'])->find($personnelId)
            : null;
    }
}
