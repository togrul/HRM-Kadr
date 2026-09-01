<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The row-level peek: enough of a personnel file to decide whether to open it.
 * Follows the module convention of addressing personnel by tabel number.
 */
class PersonnelQuickView extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public string $personnelModel;

    public function mount(): void
    {
        $this->authorize('view', $this->personnel);
    }

    #[Computed]
    public function personnel(): Personnel
    {
        return Personnel::with([
            'position',
            'educationDegree',
            // The panel prints the ancestor chain; a lazy ->parent walk is a query per level.
            'structure' => fn ($query) => $query->withRecursive('parent', false),
        ])
            ->withTrashed()
            ->where('tabel_no', $this->personnelModel)
            ->firstOrFail();
    }

    /**
     * @return list<array{label:string,value:string,mono:bool}>
     */
    #[Computed]
    public function rows(): array
    {
        $reader = app(PersonnelProfileReadService::class);
        $personnel = $this->personnel;

        return [
            ['label' => __('personnel::common.labels.structure'), 'value' => $reader->structurePath($personnel) ?: '—', 'mono' => false],
            ['label' => __('personnel::common.labels.position'), 'value' => $personnel->position_label ?: '—', 'mono' => false],
            ['label' => __('personnel::common.labels.birthdate'), 'value' => $personnel->birthdate?->format('d.m.Y') ?: '—', 'mono' => true],
            ['label' => __('personnel::common.labels.pin'), 'value' => $personnel->pin ?: '—', 'mono' => true],
            ['label' => __('personnel::common.labels.mobile'), 'value' => $personnel->mobile ?: '—', 'mono' => true],
            ['label' => __('personnel::common.labels.education_degree'), 'value' => $personnel->educationDegree?->getAttribute('title_az') ?: '—', 'mono' => false],
            ['label' => __('personnel::common.labels.join_date'), 'value' => $personnel->join_work_date?->format('d.m.Y') ?: '—', 'mono' => true],
        ];
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.quick-view');
    }
}
