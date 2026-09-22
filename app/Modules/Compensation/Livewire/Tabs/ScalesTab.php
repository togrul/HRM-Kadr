<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\PayGrade;
use App\Models\PayScale;
use App\Models\Position;
use App\Models\RankCategory;
use App\Modules\Compensation\Application\Services\SalaryScaleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\WithPagination;

/**
 * Pay scales and the grades of the selected scale.
 */
class ScalesTab extends CompensationTab
{
    use WithPagination;

    #[Locked]
    public ?int $editingScaleId = null;

    public string $scaleSearch = '';

    public array $scaleForm = [
        'name' => '',
        'regime_id' => null,
        'currency' => 'AZN',
        'effective_from' => '',
        'effective_to' => '',
        'is_active' => true,
        'description' => '',
    ];

    #[Locked]
    public ?int $selectedScaleId = null;

    #[Locked]
    public ?int $editingGradeId = null;

    public array $gradeForm = [
        'code' => '',
        'name' => '',
        'base_amount' => '',
        'rank_category_id' => null,
        'position_id' => null,
        'sort' => 0,
    ];

    public string $searchRankCategory = '';

    public string $searchPosition = '';

    /** Workspace totals shown in the table header; owned by the shell. */
    #[Reactive]
    public int $scaleCount = 0;

    #[Reactive]
    public int $gradeCount = 0;

    protected function viewName(): string
    {
        return 'scales';
    }

    protected function panels(): array
    {
        return ['scale', 'grade'];
    }

    protected function resetPanel(string $panel): void
    {
        if ($panel === 'scale') {
            $this->cancelScale();

            return;
        }

        $this->cancelGrade();
    }

    // ----------------------------------------------------------------
    // Option lists
    // ----------------------------------------------------------------

    #[Computed]
    public function rankCategoryOptions(): array
    {
        $term = trim($this->searchRankCategory);

        return RankCategory::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn ($r): array => ['id' => $r->id, 'label' => $r->name])
            ->all();
    }

    #[Computed]
    public function positionOptions(): array
    {
        $term = trim($this->searchPosition);

        return Position::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn ($p): array => ['id' => $p->id, 'label' => $p->name])
            ->all();
    }

    // ----------------------------------------------------------------
    // Scales
    // ----------------------------------------------------------------

    #[Computed]
    public function scales(): LengthAwarePaginator
    {
        $term = trim($this->scaleSearch);

        return PayScale::query()
            ->with('regime:id,name')
            ->withCount('grades')
            ->withMin('grades', 'base_amount')
            ->withMax('grades', 'base_amount')
            ->withMin('grades', 'code')
            ->withMax('grades', 'code')
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderByDesc('effective_from')
            ->paginate(8, ['*'], 'scalesPage');
    }

    /**
     * Band of a scale, derived from its grades — the table has no min/mid/max columns of its own.
     * Midpoint is the standard (min + max) / 2, and every figure obeys the amounts permission.
     *
     * @return array{grades: int, range: string, min: string, midpoint: string, max: string}
     */
    public function scaleRange(PayScale $scale): array
    {
        $min = $scale->getAttribute('grades_min_base_amount');
        $max = $scale->getAttribute('grades_max_base_amount');
        $first = $scale->getAttribute('grades_min_code');
        $last = $scale->getAttribute('grades_max_code');

        $show = fn (?float $value): string => match (true) {
            $value === null => '—',
            ! $this->canViewAmounts() => '•••',
            default => number_format($value, 0, ',', ' '),
        };

        return [
            'grades' => (int) $scale->getAttribute('grades_count'),
            'range' => $first === null ? '—' : ($first === $last ? (string) $first : $first.'–'.$last),
            'min' => $show($min === null ? null : (float) $min),
            'midpoint' => $show(($min === null || $max === null) ? null : ((float) $min + (float) $max) / 2),
            'max' => $show($max === null ? null : (float) $max),
        ];
    }

    public function updatedScaleSearch(): void
    {
        $this->resetPage('scalesPage');
    }

    public function editScale(int $id): void
    {
        $scale = PayScale::findOrFail($id);
        $this->editingScaleId = $scale->id;
        $this->panel = 'scale';
        $this->scaleForm = [
            'name' => $scale->name,
            'regime_id' => $scale->regime_id,
            'currency' => $scale->currency,
            'effective_from' => optional($scale->effective_from)->toDateString() ?? '',
            'effective_to' => optional($scale->effective_to)->toDateString() ?? '',
            'is_active' => (bool) $scale->is_active,
            'description' => $scale->description ?? '',
        ];
        $this->resetValidation();
    }

    public function saveScale(SalaryScaleService $service): void
    {
        $this->guardManage();

        $data = $this->validate([
            'scaleForm.name' => 'required|string|max:255',
            'scaleForm.regime_id' => 'required|exists:compensation_regimes,id',
            'scaleForm.currency' => 'required|string|size:3',
            'scaleForm.effective_from' => 'required|date',
            'scaleForm.effective_to' => 'nullable|date|after_or_equal:scaleForm.effective_from',
            'scaleForm.is_active' => 'boolean',
            'scaleForm.description' => 'nullable|string|max:2000',
        ], attributes: $this->fieldLabels([
            'scaleForm.name' => 'name',
            'scaleForm.regime_id' => 'regime',
            'scaleForm.currency' => 'currency',
            'scaleForm.effective_from' => 'effective_from',
            'scaleForm.effective_to' => 'effective_to',
            'scaleForm.is_active' => 'is_active',
            'scaleForm.description' => 'description',
        ]))['scaleForm'];

        $data['effective_to'] = $data['effective_to'] ?: null;

        if ($this->editingScaleId) {
            $service->updateScale(PayScale::findOrFail($this->editingScaleId), $data);
        } else {
            $service->createScale($data);
        }

        $this->cancelScale();
        $this->announce('saved');
    }

    public function deleteScale(int $id, SalaryScaleService $service): void
    {
        $this->guardManage();
        $service->deleteScale(PayScale::findOrFail($id));

        if ($this->editingScaleId === $id) {
            $this->cancelScale();
        }
        if ($this->selectedScaleId === $id) {
            $this->selectedScaleId = null;
        }

        $this->resetPage('scalesPage');
        $this->announce('deleted');
    }

    public function cancelScale(): void
    {
        $this->editingScaleId = null;
        $this->scaleForm = [
            'name' => '', 'regime_id' => null, 'currency' => 'AZN',
            'effective_from' => '', 'effective_to' => '', 'is_active' => true, 'description' => '',
        ];
        $this->panel = '';
        $this->resetValidation();
    }

    // ----------------------------------------------------------------
    // Grades
    // ----------------------------------------------------------------

    public function selectScale(int $id): void
    {
        $this->selectedScaleId = $id;
        $this->cancelGrade();
    }

    #[Computed]
    public function grades(): Collection
    {
        if (! $this->selectedScaleId) {
            return collect();
        }

        return PayGrade::query()
            ->where('pay_scale_id', $this->selectedScaleId)
            ->with(['rankCategory:id,name', 'position:id,name'])
            ->orderBy('sort')
            ->get();
    }

    public function editGrade(int $id): void
    {
        $grade = PayGrade::findOrFail($id);
        $this->selectedScaleId = $grade->pay_scale_id;
        $this->editingGradeId = $grade->id;
        $this->panel = 'grade';
        $this->gradeForm = [
            'code' => $grade->code,
            'name' => $grade->name,
            'base_amount' => (string) $grade->base_amount,
            'rank_category_id' => $grade->rank_category_id,
            'position_id' => $grade->position_id,
            'sort' => $grade->sort,
        ];
        $this->resetValidation();
    }

    public function saveGrade(SalaryScaleService $service): void
    {
        $this->guardManage();
        abort_unless($this->selectedScaleId !== null, 422);

        $data = $this->validate([
            'gradeForm.code' => 'required|string|max:64',
            'gradeForm.name' => 'required|string|max:255',
            'gradeForm.base_amount' => 'required|numeric|min:0',
            'gradeForm.rank_category_id' => 'nullable|exists:rank_categories,id',
            'gradeForm.position_id' => 'nullable|exists:positions,id',
            'gradeForm.sort' => 'nullable|integer|min:0',
        ], attributes: $this->fieldLabels([
            'gradeForm.code' => 'code',
            'gradeForm.name' => 'name',
            'gradeForm.base_amount' => 'base_amount',
            'gradeForm.rank_category_id' => 'rank_category',
            'gradeForm.position_id' => 'position',
            'gradeForm.sort' => 'sort',
        ]))['gradeForm'];

        $data['pay_scale_id'] = $this->selectedScaleId;
        $data['sort'] = $data['sort'] ?? 0;

        if ($this->editingGradeId) {
            $service->updateGrade(PayGrade::findOrFail($this->editingGradeId), $data);
        } else {
            $service->createGrade($data);
        }

        $this->cancelGrade();
        $this->announce('saved');
    }

    public function deleteGrade(int $id, SalaryScaleService $service): void
    {
        $this->guardManage();
        $service->deleteGrade(PayGrade::findOrFail($id));

        if ($this->editingGradeId === $id) {
            $this->cancelGrade();
        }

        $this->announce('deleted');
    }

    public function cancelGrade(): void
    {
        $this->editingGradeId = null;
        $this->gradeForm = [
            'code' => '', 'name' => '', 'base_amount' => '',
            'rank_category_id' => null, 'position_id' => null, 'sort' => 0,
        ];
        $this->panel = '';
        $this->resetValidation();
    }
}
