<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\Candidate;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Services\StructureScope;
use App\Services\StructureService;

/**
 * Who an order is about: validates that the composer picked the subject a template
 * needs, and builds the Personnel the document's employee.* variables resolve from.
 */
class OrderSubjectResolver
{
    /** Candidate status offered to hire orders: "ready for order" (Əmrə hazır). */
    public const CANDIDATE_READY_FOR_ORDER = 30;

    /** Minimum search term length before the pickers query anything. */
    private const MIN_TERM_LENGTH = 2;

    private const RESULT_LIMIT = 8;

    /**
     * Hire-order candidate picker: only candidates ready for an order.
     *
     * @return array<int,array{id:int,label:string}>
     */
    public function searchCandidates(string $term): array
    {
        $term = trim($term);
        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return [];
        }

        return Candidate::query()
            ->where('status_id', self::CANDIDATE_READY_FOR_ORDER)
            ->where(fn ($q) => $q
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('patronymic', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'surname', 'name', 'patronymic'])
            ->map(fn (Candidate $c): array => [
                'id' => $c->id,
                'label' => trim("{$c->surname} {$c->name} {$c->patronymic}"),
            ])
            ->all();
    }

    /**
     * Employee picker: active employees by name or tabel number.
     *
     * @return array<int,array{id:int,label:string}>
     */
    public function searchPersonnel(string $term): array
    {
        $term = trim($term);
        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return [];
        }

        return $this->scope()->constrain(Personnel::query(), 'personnels.structure_id')
            ->active()
            ->where(fn ($q) => $q->nameLike($term)->orWhere('tabel_no', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
            ->map(fn (Personnel $p): array => [
                'id' => $p->id,
                'label' => trim("{$p->surname} {$p->name} {$p->patronymic}")." ({$p->tabel_no})",
            ])
            ->all();
    }

    /**
     * A picked candidate's id, display name and home structure (the default hire target).
     *
     * @return array{id:int,label:?string,structure_id:?int}|null
     */
    public function candidatePick(int $candidateId): ?array
    {
        $candidate = Candidate::find($candidateId);

        return $candidate ? [
            'id' => $candidate->id,
            'label' => $candidate->fullname,
            'structure_id' => $candidate->structure_id,
        ] : null;
    }

    /**
     * @return array{id:int,label:?string}|null
     */
    public function personnelPick(int $personnelId): ?array
    {
        $personnel = Personnel::find($personnelId);

        if ($personnel && ! $this->scope()->allows($personnel->structure_id)) {
            return null;
        }

        return $personnel ? ['id' => $personnel->id, 'label' => $personnel->fullname] : null;
    }

    /**
     * İşçi seçimləri cari istifadəçinin struktur görünürlüyü ilə məhdudlaşır (fail closed).
     */
    private function scope(): StructureScope
    {
        return app(StructureService::class)->scopeFor();
    }

    /**
     * Seçilmiş subyektlər (işçi, iştirakçılar, işə qəbul hədəf strukturu)
     * istifadəçinin strukturlarından kənardadırsa xəta qaytarır — müştəri tərəfdən
     * göndərilən id-lər serverdə yoxlanılır.
     *
     * @return array<string,string>
     */
    public function scopeErrors(OrderComposition $composition, ?StructureScope $scope = null): array
    {
        $scope ??= $this->scope();

        if ($scope->isAll()) {
            return [];
        }

        if ($composition->personnelId && ! $scope->allows(Personnel::withTrashed()->whereKey($composition->personnelId)->value('structure_id'))) {
            return ['personnelId' => __('orders::order_composer.errors.subject_out_of_scope')];
        }

        $participantIds = array_map('intval', array_column($composition->participantList(), 'personnel_id'));
        if ($participantIds !== []) {
            $structureIds = Personnel::withTrashed()->whereKey($participantIds)->pluck('structure_id', 'id');

            foreach ($participantIds as $id) {
                if (! $scope->allows($structureIds[$id] ?? null)) {
                    return ['participants' => __('orders::order_composer.errors.subject_out_of_scope')];
                }
            }
        }

        if ($composition->hireStructureId && ! $scope->allows($composition->hireStructureId)) {
            return ['hireStructureId' => __('orders::order_composer.errors.hire_structure_out_of_scope')];
        }

        return [];
    }

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

        if ($template->isMultiParticipant()) {
            return $this->participantErrors($composition);
        }

        $needsEmployee = collect($template->variables ?? [])
            ->contains(fn ($v): bool => ($v['source'] ?? '') === 'auto' && str_starts_with((string) ($v['auto_key'] ?? ''), 'employee.'));

        if ($needsEmployee && ! $composition->personnelId) {
            return ['personnelId' => __('orders::order_composer.errors.personnel_required')];
        }

        return [];
    }

    /**
     * A multi-participant order needs at least one participant and no one twice.
     *
     * @return array<string,string>
     */
    private function participantErrors(OrderComposition $composition): array
    {
        $ids = array_column($composition->participantList(), 'personnel_id');

        if ($ids === []) {
            return ['participants' => __('orders::order_composer.errors.participants_required')];
        }

        $duplicates = array_keys(array_filter(array_count_values($ids), fn (int $count): bool => $count > 1));
        if ($duplicates !== []) {
            return ['participants' => __('orders::order_composer.errors.participant_duplicate', [
                'name' => $this->personnel((int) $duplicates[0])->fullname ?? '#'.$duplicates[0],
            ])];
        }

        return [];
    }

    /**
     * The participants of a multi-participant order with the structure/position the
     * document declines, in the composition's order (unknown ids dropped), loaded at once.
     *
     * @param  list<int>  $ids
     * @return list<Personnel>
     */
    public function participants(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $people = Personnel::with(['structure:id,name', 'position:id,name'])->whereKey($ids)->get()->keyBy('id');

        $ordered = [];
        foreach ($ids as $id) {
            if ($people->has($id)) {
                $ordered[] = $people->get($id);
            }
        }

        return $ordered;
    }

    /**
     * Display labels for the composer's participant list, keyed by id.
     *
     * @param  list<int>  $ids
     * @return array<int,string>
     */
    public function participantLabels(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->scope()->constrain(Personnel::query()->whereKey($ids), 'personnels.structure_id')->get(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
            ->mapWithKeys(fn (Personnel $p): array => [(int) $p->id => trim("{$p->surname} {$p->name} {$p->patronymic}")." ({$p->tabel_no})"])
            ->all();
    }

    /**
     * The person the document's employee.* variables resolve from: the picked employee,
     * or (for hire) a transient employee built from the candidate + the structure/
     * position they are hired into — so names/structure/position decline correctly.
     */
    public function subject(OrderWordTemplate $template, OrderComposition $composition): ?Personnel
    {
        if ($template->isMultiParticipant()) {
            return $this->personnel($composition->leadPersonnelId());
        }

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
