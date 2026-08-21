<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\PayGrade;
use App\Models\PayScale;
use Illuminate\Support\Carbon;

class SalaryScaleService
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function createScale(array $data): PayScale
    {
        return PayScale::create($data);
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function updateScale(PayScale $scale, array $data): PayScale
    {
        $scale->update($data);

        return $scale;
    }

    public function deleteScale(PayScale $scale): void
    {
        $scale->delete();
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function createGrade(array $data): PayGrade
    {
        return PayGrade::create($data);
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function updateGrade(PayGrade $grade, array $data): PayGrade
    {
        $grade->update($data);

        return $grade;
    }

    public function deleteGrade(PayGrade $grade): void
    {
        $grade->delete();
    }

    /**
     * The active pay scale for a regime effective on the given date.
     */
    public function effectiveScale(int $regimeId, ?Carbon $date = null): ?PayScale
    {
        $on = ($date ?? now())->toDateString();

        return PayScale::query()
            ->where('regime_id', $regimeId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->orderByDesc('effective_from')
            ->first();
    }

    public function baseForGrade(int $payGradeId): ?string
    {
        return PayGrade::query()->whereKey($payGradeId)->value('base_amount');
    }
}
