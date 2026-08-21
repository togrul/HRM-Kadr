<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\EmployeeCompensation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompensationService
{
    /**
     * Assign a new effective-dated compensation, closing any currently active one.
     *
     * @param  array<string,mixed>  $data
     * @param  array<int,array<string,mixed>>  $lines
     */
    public function assignCompensation(string $tabelNo, array $data, array $lines = []): EmployeeCompensation
    {
        return DB::transaction(function () use ($tabelNo, $data, $lines): EmployeeCompensation {
            $effectiveFrom = Carbon::parse($data['effective_from']);

            $this->endActive($tabelNo, $effectiveFrom);

            $compensation = EmployeeCompensation::create([
                'tabel_no' => $tabelNo,
                'regime_id' => $data['regime_id'],
                'pay_grade_id' => $data['pay_grade_id'] ?? null,
                'base_amount' => $data['base_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'AZN',
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => null,
                'status' => 'active',
                'order_no' => $data['order_no'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncLines($compensation, $lines);

            return $compensation;
        });
    }

    /**
     * @param  array<int,array<string,mixed>>  $lines
     */
    public function updateLines(EmployeeCompensation $compensation, array $lines): EmployeeCompensation
    {
        DB::transaction(fn () => $this->syncLines($compensation, $lines));

        return $compensation->refresh();
    }

    public function endCompensation(EmployeeCompensation $compensation, ?Carbon $endDate = null): EmployeeCompensation
    {
        $compensation->update([
            'status' => 'ended',
            'effective_to' => ($endDate ?? now())->toDateString(),
        ]);

        return $compensation;
    }

    /**
     * Seed a DRAFT compensation for a newly-hired employee (from the candidate offer salary).
     * Draft (not active) because the regime/exact terms still need HR confirmation before payroll.
     */
    public function createDraftForHire(string $tabelNo, float $baseAmount, ?string $currency, ?Carbon $effectiveFrom, ?string $orderNo): ?EmployeeCompensation
    {
        $regimeId = \App\Models\CompensationRegime::query()->where('is_active', true)->orderBy('sort')->value('id');

        if (! $regimeId) {
            return null;
        }

        return EmployeeCompensation::create([
            'tabel_no' => $tabelNo,
            'regime_id' => $regimeId,
            'base_amount' => round($baseAmount, 2),
            'currency' => $currency ?: 'AZN',
            'effective_from' => ($effectiveFrom ?? now())->toDateString(),
            'effective_to' => null,
            'status' => 'draft',
            'order_no' => $orderNo,
            'note' => 'auto: hire',
        ]);
    }

    /**
     * On transfer, if the new position maps to a pay grade in the employee's regime, seed a DRAFT
     * compensation suggesting the regraded base. Draft so HR confirms before payroll changes.
     */
    public function suggestRegradeFromTransfer(string $tabelNo, int $positionId, ?string $orderNo): ?EmployeeCompensation
    {
        $current = $this->currentFor($tabelNo);

        if (! $current) {
            return null;
        }

        $grade = \App\Models\PayGrade::query()
            ->where('position_id', $positionId)
            ->whereHas('payScale', fn ($q) => $q->where('regime_id', $current->regime_id)->where('is_active', true))
            ->orderByDesc('id')
            ->first();

        if (! $grade) {
            return null;
        }

        return EmployeeCompensation::create([
            'tabel_no' => $tabelNo,
            'regime_id' => $current->regime_id,
            'pay_grade_id' => $grade->id,
            'base_amount' => $grade->base_amount,
            'currency' => $current->currency,
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
            'status' => 'draft',
            'order_no' => $orderNo,
            'note' => 'auto: transfer',
        ]);
    }

    public function removeTransferSuggestion(?string $orderNo): void
    {
        if (! $orderNo) {
            return;
        }

        EmployeeCompensation::query()
            ->where('order_no', $orderNo)
            ->where('note', 'auto: transfer')
            ->where('status', 'draft')
            ->delete();
    }

    /**
     * End the employee's active compensation when an employment termination takes effect.
     */
    public function endActiveForTermination(string $tabelNo, ?Carbon $date = null): ?EmployeeCompensation
    {
        $compensation = $this->currentFor($tabelNo, $date);

        if (! $compensation) {
            return null;
        }

        return $this->endCompensation($compensation, $date);
    }

    /**
     * Reverse a termination: re-activate the most recent compensation that the termination ended.
     * ponytail: termination ends the single currently-active record, so the latest record is it;
     * upgrade to an explicit ended-by-order link if multi-effect ordering ever matters.
     */
    public function reactivateAfterTermination(string $tabelNo): ?EmployeeCompensation
    {
        $compensation = EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($compensation && $compensation->status === 'ended') {
            $compensation->update(['status' => 'active', 'effective_to' => null]);
        }

        return $compensation;
    }

    public function currentFor(string $tabelNo, ?Carbon $date = null): ?EmployeeCompensation
    {
        $on = ($date ?? now())->toDateString();

        return EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->orderByDesc('effective_from')
            ->with('lines.component')
            ->first();
    }

    /**
     * @return Collection<int,EmployeeCompensation>
     */
    public function historyFor(string $tabelNo): Collection
    {
        return EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->orderByDesc('effective_from')
            ->with(['regime', 'payGrade'])
            ->get();
    }

    private function endActive(string $tabelNo, Carbon $newEffectiveFrom): void
    {
        EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->get()
            ->each(fn (EmployeeCompensation $existing) => $existing->update([
                'status' => 'ended',
                'effective_to' => EffectiveDating::dayBefore($newEffectiveFrom)->toDateString(),
            ]));
    }

    /**
     * Replace the compensation's component lines with the supplied set.
     *
     * @param  array<int,array<string,mixed>>  $lines
     */
    private function syncLines(EmployeeCompensation $compensation, array $lines): void
    {
        $compensation->lines()->delete();

        foreach ($lines as $line) {
            if (empty($line['component_id'])) {
                continue;
            }

            $compensation->lines()->create([
                'component_id' => $line['component_id'],
                'amount' => $line['amount'] ?? null,
                'percent' => $line['percent'] ?? null,
                'note' => $line['note'] ?? null,
            ]);
        }
    }
}
