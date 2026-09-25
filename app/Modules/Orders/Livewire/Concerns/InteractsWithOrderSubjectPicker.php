<?php

namespace App\Modules\Orders\Livewire\Concerns;

use App\Modules\Orders\Infrastructure\Document\OrderSubjectResolver;
use Livewire\Attributes\Locked;

/**
 * The order composer's subject pickers: an employee for most orders, or (for hire
 * orders) a candidate plus the structure/position they are hired into — on approval
 * the candidate becomes an active employee. Picked ids are server-set only.
 */
trait InteractsWithOrderSubjectPicker
{
    #[Locked]
    public ?int $personnelId = null;

    public string $personnelQuery = '';

    public ?string $personnelLabel = null;

    #[Locked]
    public ?int $candidateId = null;

    public string $candidateQuery = '';

    public ?string $candidateLabel = null;

    public ?int $hireStructureId = null;

    public ?int $hirePositionId = null;

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function getCandidateResultsProperty(OrderSubjectResolver $subjects): array
    {
        return $subjects->searchCandidates($this->candidateQuery);
    }

    public function selectCandidate(int $id): void
    {
        $candidate = app(OrderSubjectResolver::class)->candidatePick($id);
        if ($candidate) {
            $this->candidateId = $candidate['id'];
            $this->candidateLabel = $candidate['label'];
            $this->hireStructureId ??= $candidate['structure_id'];
        }
        $this->candidateQuery = '';
        $this->previewPdf = '';
    }

    public function clearCandidate(): void
    {
        $this->candidateId = null;
        $this->candidateLabel = null;
        $this->previewPdf = '';
    }

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function getPersonnelResultsProperty(OrderSubjectResolver $subjects): array
    {
        return $subjects->searchPersonnel($this->personnelQuery);
    }

    public function selectPersonnel(int $id): void
    {
        $personnel = app(OrderSubjectResolver::class)->personnelPick($id);
        if ($personnel) {
            $this->personnelId = $personnel['id'];
            $this->personnelLabel = $personnel['label'];
        }
        $this->personnelQuery = '';
        $this->previewPdf = '';
    }

    public function clearPersonnel(): void
    {
        $this->personnelId = null;
        $this->personnelLabel = null;
        $this->previewPdf = '';
    }

    /**
     * Restore a picked employee (e.g. from a deep link or an order being edited).
     */
    private function pickPersonnel(?int $personnelId): void
    {
        $this->personnelId = $personnelId;
        if ($personnelId) {
            $this->personnelLabel = app(OrderSubjectResolver::class)->personnelPick($personnelId)['label'] ?? null;
        }
    }

    /**
     * Restore a hire order's candidate and target post.
     */
    private function pickHire(?int $candidateId, ?int $structureId, ?int $positionId): void
    {
        $this->hireStructureId = $structureId;
        $this->hirePositionId = $positionId;
        if ($candidateId) {
            $this->candidateId = $candidateId;
            $this->candidateLabel = app(OrderSubjectResolver::class)->candidatePick($candidateId)['label'] ?? null;
        }
    }

    /**
     * Switching order types clears any hire subject picked for the previous one.
     */
    private function resetHireSubject(): void
    {
        $this->candidateId = null;
        $this->candidateLabel = null;
        $this->hireStructureId = null;
        $this->hirePositionId = null;
    }
}
