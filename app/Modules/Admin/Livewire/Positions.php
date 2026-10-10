<?php

namespace App\Modules\Admin\Livewire;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Position;
use App\Models\RankCategory;
use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Support\PositionLevel;
use App\Support\VtiskCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['positionUpdated', 'deleted'])]
class Positions extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use DropdownConstructTrait;

    public string $searchRankCategory = '';

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:positions,id'.($this->model ? ','.$this->model->id : ''),
            'form.name' => 'required|string|min:2',
            'form.rank_category_id' => 'nullable|integer|exists:rank_categories,id',
            'form.approval_rank' => 'required|integer|min:0|max:999',
            'form.is_approval_target' => 'boolean',
            'form.level' => 'nullable|integer|in:'.implode(',', PositionLevel::LEVELS),
            'form.vtisk_category' => 'nullable|string|in:'.implode(',', VtiskCategory::ALL),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('admin::references.fields.id'),
            'form.name' => __('admin::references.fields.name'),
            'form.rank_category_id' => __('admin::references.fields.rank_category'),
            'form.approval_rank' => __('admin::references.fields.approval_rank'),
            'form.is_approval_target' => __('admin::references.fields.is_approval_target'),
            'form.level' => __('admin::references.fields.position_level'),
            'form.vtisk_category' => __('admin::references.fields.vtisk_category'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'rank_category_id' => null,
            'approval_rank' => 0,
            'is_approval_target' => true,
            'level' => null,
            'vtisk_category' => null,
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Position::with('rankCategory:id,name')->find($id)
            : null;

        $this->form = $this->formDefaults();

        if ($this->model) {
            $this->form['id'] = $this->model->id;
            $this->form['name'] = $this->model->name;
            $this->form['rank_category_id'] = $this->model->rank_category_id;
            $this->form['approval_rank'] = $this->model->approval_rank ?? 0;
            $this->form['is_approval_target'] = (bool) ($this->model->is_approval_target ?? true);
            $this->form['level'] = $this->model->level;
            $this->form['vtisk_category'] = $this->model->vtisk_category;
        }
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Position::find($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        Gate::authorize('access-admin');

        $this->validate();

        $this->form['vtisk_category'] = filled($this->form['vtisk_category'] ?? null) ? $this->form['vtisk_category'] : null;

        $this->model
            ? $this->model->update(Arr::except($this->form, 'id'))
            : Position::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('positionUpdated');
        $this->closeCrud();
    }

    public function render(): View
    {
        $positions = Position::all();

        return view('admin::livewire.admin.positions', compact('positions'));
    }

    public function rankCategoryOptions(): array
    {
        $base = RankCategory::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchRankCategory'),
            selectedId: data_get($this->form, 'rank_category_id'),
            limit: 80
        );
    }
}
