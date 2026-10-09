<?php

namespace App\Modules\Candidates\Application\Services;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Personnel;
use App\Modules\Orders\Contracts\HireOrderTemplates;
use App\Services\Modules\ModuleState;
use App\Support\Database\InstalledTables;

/**
 * Candidate → hire flow. In Azerbaijan a hire is formalised by an employment contract and
 * an "İşə qəbul" order, so a candidate is never turned into an employee directly: HR
 * prepares the hire order from the candidate (the composer opens prefilled), and only when
 * that order is approved does the Orders module create the employee — which calls back into
 * CandidateHireConversionService, where recordHire() links the candidate to the new
 * employee and the order and marks them "Qəbul olundu".
 */
class CandidateHireOrderService
{
    /** AppealStatus "Əmrə hazır" — the candidate is ready for a hire order. */
    public const STATUS_READY_FOR_ORDER = 30;

    /** AppealStatus "Qəbul olundu" — hired by an approved order. */
    public const STATUS_HIRED = 70;

    /** AppealStatus "Dayandırıldı" — the candidacy was stopped. */
    public const STATUS_STOPPED = 90;

    /** Statuses after which no hire order can be prepared. */
    public const FINAL_STATUSES = [self::STATUS_HIRED, self::STATUS_STOPPED];

    private ?string $hireTemplateCode = null;

    private bool $hireTemplateResolved = false;

    public function __construct(
        private readonly HireOrderTemplates $hireTemplates,
        private readonly ModuleState $modules,
    ) {}

    /**
     * Whether the candidate is still open for a hire order (not deleted, not hired, not
     * stopped). Says nothing about permissions or whether a hire template exists.
     */
    public function isEligible(Candidate $candidate): bool
    {
        if ($candidate->trashed() || $candidate->hired_personnel_id) {
            return false;
        }

        return ! in_array((int) $candidate->status_id, self::FINAL_STATUSES, true);
    }

    /**
     * The hire order type to preset, or null when Orders is off or has no hire template.
     * Memoised on the instance: a list resolves the service once and asks per row.
     */
    public function hireTemplateCode(): ?string
    {
        if (! $this->hireTemplateResolved) {
            $code = $this->modules->enabled('orders') ? $this->hireTemplates->hireTemplateCode() : null;
            $this->hireTemplateCode = $code !== null && $code !== '' ? $code : null;
            $this->hireTemplateResolved = true;
        }

        return $this->hireTemplateCode;
    }

    public function canPrepare(Candidate $candidate): bool
    {
        return $this->isEligible($candidate) && $this->hireTemplateCode() !== null;
    }

    /**
     * Mount parameters for the Orders composer (`<livewire:orders.order-composer>`): the
     * hire preset, the candidate as the order subject, and the target post — the
     * candidate's structure, or the structure/position of the vacancy the candidate
     * applied to when there is one. Names, birthdate, gender and phone reach the order
     * document from the candidate itself (the composer resolves employee.* variables from
     * the selected candidate), so they are not duplicated here.
     *
     * @return array{presetCode:string,candidateId:int,candidateLabel:string,hireStructureId:int|null,hirePositionId:int|null}|null
     */
    public function composerParameters(Candidate $candidate): ?array
    {
        $code = $this->hireTemplateCode();

        if ($code === null || ! $this->isEligible($candidate)) {
            return null;
        }

        $opening = $this->latestOpeningTarget($candidate);

        return [
            'presetCode' => $code,
            'candidateId' => (int) $candidate->id,
            'candidateLabel' => trim((string) $candidate->fullname),
            'hireStructureId' => $opening['structure_id'] ?? ($candidate->structure_id ? (int) $candidate->structure_id : null),
            'hirePositionId' => $opening['position_id'] ?? null,
        ];
    }

    /**
     * Link the candidate to the employee created by an approved hire order and mark them
     * "Qəbul olundu". Idempotent: re-running keeps the first link.
     */
    public function recordHire(Candidate $candidate, Personnel $personnel, ?int $orderId = null, ?string $orderNo = null): void
    {
        if (! InstalledTables::hasColumn('candidates', 'hired_personnel_id')) {
            $candidate->forceFill(['status_id' => self::STATUS_HIRED])->save();

            return;
        }

        $candidate->forceFill([
            'status_id' => self::STATUS_HIRED,
            'hired_personnel_id' => $candidate->hired_personnel_id ?: $personnel->id,
            'hire_order_id' => $candidate->hire_order_id ?: $orderId,
            'hire_order_no' => $candidate->hire_order_no ?: ($orderNo !== null && $orderNo !== '' ? $orderNo : null),
            'hired_at' => $candidate->hired_at ?? now(),
        ])->save();
    }

    /**
     * @return array{structure_id:int|null,position_id:int|null}|null
     */
    private function latestOpeningTarget(Candidate $candidate): ?array
    {
        $application = $candidate->relationLoaded('latestApplication')
            ? $candidate->latestApplication
            : CandidateApplication::query()
                ->where('candidate_id', $candidate->id)
                ->latest('id')
                ->with('opening:id,structure_id,position_id')
                ->first();

        $opening = $application?->opening;

        if (! $opening) {
            return null;
        }

        return [
            'structure_id' => $opening->structure_id ? (int) $opening->structure_id : null,
            'position_id' => $opening->position_id ? (int) $opening->position_id : null,
        ];
    }
}
