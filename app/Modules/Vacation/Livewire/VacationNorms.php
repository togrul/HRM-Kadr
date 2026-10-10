<?php

namespace App\Modules\Vacation\Livewire;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\VacationNorm;
use App\Modules\Vacation\Application\Services\VacationNormCatalog;
use App\Services\StructureService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin → «Məzuniyyət normaları»: dörd qrup (əsas, staj, uşaqlı valideyn, əmək şəraiti).
 * Qaydalar və yoxlamalar VacationNormCatalog-dadır; komponent yalnız formu idarə edir.
 */
class VacationNorms extends Component
{
    #[Url(as: 'group')]
    public string $group = VacationNorm::GROUP_BASE;

    public bool $isAdded = false;

    public ?int $editingId = null;

    public ?int $pendingDeleteId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public string $searchPosition = '';

    public string $searchPersonnel = '';

    public function mount(): void
    {
        Gate::authorize('access-admin');

        if (! in_array($this->group, VacationNorm::GROUPS, true)) {
            $this->group = VacationNorm::GROUP_BASE;
        }

        $this->form = $this->defaults();
    }

    public function selectGroup(string $group): void
    {
        if (in_array($group, VacationNorm::GROUPS, true)) {
            $this->group = $group;
            $this->closeCrud();
        }
    }

    public function openCrud(?int $id = null): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = $this->defaults();

        if ($id !== null) {
            $norm = VacationNorm::query()->with('personnel:id,tabel_no')->findOrFail($id);
            $this->editingId = $norm->id;
            $this->form = [
                'scope' => $norm->scope,
                'position_id' => $norm->position_id,
                'personnel_id' => $norm->personnel?->id,
                'condition' => $norm->condition,
                'min_value' => $norm->min_value,
                'max_value' => $norm->max_value,
                'women_only' => $norm->women_only,
                'exclusive' => $norm->exclusive,
                'days' => $norm->days,
                'is_active' => $norm->is_active,
                'legal_basis' => $norm->legal_basis,
                'note' => $norm->note,
                'valid_from' => $norm->valid_from?->toDateString(),
                'valid_to' => $norm->valid_to?->toDateString(),
                'not_in_conditions' => $norm->not_in_conditions,
            ];
        }

        $this->isAdded = true;
    }

    public function closeCrud(): void
    {
        $this->isAdded = false;
        $this->editingId = null;
        $this->form = $this->defaults();
        $this->resetValidation();
    }

    public function store(VacationNormCatalog $catalog): void
    {
        Gate::authorize('access-admin');

        $this->validate($catalog->rules($this->group, $this->form['personnel_id'] ?? null, $this->form), [], $this->validationAttributes());

        $personnelId = $this->form['personnel_id'] ?? null;
        abort_if(filled($personnelId) && ! app(StructureService::class)->allowsPersonnelId(auth()->user(), $personnelId), 403);

        try {
            $catalog->save($this->group, $this->form, $this->editingId);
        } catch (DomainException $e) {
            $this->addError('form.days', $e->getMessage());

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('vacation::norms.messages.saved'));
        $this->closeCrud();
    }

    public function toggle(int $id, VacationNormCatalog $catalog): void
    {
        Gate::authorize('access-admin');
        $catalog->toggle($id);
    }

    /** Asks the global confirm modal; on confirm it calls delete(). */
    public function confirmDelete(int $id): void
    {
        $this->pendingDeleteId = $id;

        $this->dispatch(
            'confirm-action',
            title: __('vacation::norms.confirm.delete_title'),
            message: __('vacation::norms.confirm.delete_text'),
            confirmText: __('ui::common.swal.yes_delete_it'),
            tone: 'rose',
            wireId: $this->getId(),
            method: 'delete',
        );
    }

    public function delete(VacationNormCatalog $catalog): void
    {
        Gate::authorize('access-admin');

        if ($this->pendingDeleteId === null) {
            return;
        }

        try {
            $catalog->delete($this->pendingDeleteId);
            $this->dispatch('notify', type: 'success', message: __('ui::common.messages.record_deleted'));
        } catch (DomainException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        $this->pendingDeleteId = null;
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function positionOptions(): array
    {
        $term = mb_strtolower(trim($this->searchPosition));
        $selected = data_get($this->form, 'position_id');

        $rows = Position::query()
            ->select('id', 'name')
            ->when($term !== '', fn ($query) => $query->whereRaw('lower(name) like ?', ["%{$term}%"]))
            ->orderBy('name')
            ->limit(80)
            ->get();

        if ($selected && ! $rows->contains('id', (int) $selected)) {
            $rows->prepend(Position::query()->select('id', 'name')->find((int) $selected));
        }

        return $rows->filter()->map(fn (Position $p): array => ['id' => (int) $p->id, 'label' => (string) $p->name])->values()->all();
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function personnelOptions(): array
    {
        $term = mb_strtolower(trim($this->searchPersonnel));
        $selected = data_get($this->form, 'personnel_id');

        $scope = app(StructureService::class)->scopeFor();

        $rows = $scope->constrain(Personnel::query(), 'structure_id')
            ->select('id', 'tabel_no', 'surname', 'name', 'patronymic')
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('lower(surname) like ?', ["%{$term}%"])
                ->orWhereRaw('lower(name) like ?', ["%{$term}%"])
                ->orWhereRaw('lower(tabel_no) like ?', ["%{$term}%"])))
            ->orderBy('surname')
            ->limit(50)
            ->get();

        if ($selected && ! $rows->contains('id', (int) $selected)) {
            $rows->prepend($scope->constrain(Personnel::query(), 'structure_id')->select('id', 'tabel_no', 'surname', 'name', 'patronymic')->find((int) $selected));
        }

        return $rows->filter()->map(fn (Personnel $p): array => [
            'id' => (int) $p->id,
            'label' => trim("{$p->surname} {$p->name} {$p->patronymic}").' · '.$p->tabel_no,
        ])->values()->all();
    }

    public function render(VacationNormCatalog $catalog): View
    {
        return view('vacation::livewire.vacation.norms', [
            'norms' => $catalog->forGroup($this->group),
            'counts' => $catalog->counts(),
            'groups' => VacationNorm::GROUPS,
            'scopes' => VacationNorm::scopesByGroup()[$this->group],
            'catalog' => $catalog,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        $scopes = VacationNorm::scopesByGroup()[$this->group] ?? [VacationNorm::SCOPE_ALL];

        return [
            'scope' => $scopes[0],
            'position_id' => null,
            'personnel_id' => null,
            'condition' => $this->group === VacationNorm::GROUP_CHILDREN ? VacationNorm::CONDITION_CHILDREN_UNDER_14 : null,
            'min_value' => null,
            'max_value' => null,
            'women_only' => $this->group === VacationNorm::GROUP_CHILDREN,
            'exclusive' => false,
            'days' => null,
            'is_active' => true,
            'legal_basis' => null,
            'note' => null,
            'valid_from' => null,
            'valid_to' => null,
            'not_in_conditions' => false,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'form.scope' => __('vacation::norms.fields.scope'),
            'form.position_id' => __('vacation::norms.fields.position'),
            'form.personnel_id' => __('vacation::norms.fields.personnel'),
            'form.condition' => __('vacation::norms.fields.condition'),
            'form.min_value' => __('vacation::norms.fields.min_value_'.$this->group),
            'form.max_value' => __('vacation::norms.fields.max_value_'.$this->group),
            'form.days' => __('vacation::norms.fields.days'),
            'form.legal_basis' => __('vacation::norms.fields.legal_basis'),
            'form.note' => __('vacation::norms.fields.note'),
            'form.valid_from' => __('vacation::norms.fields.valid_from'),
            'form.valid_to' => __('vacation::norms.fields.valid_to'),
        ];
    }
}
