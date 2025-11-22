<?php

namespace App\Modules\Admin\Livewire;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\City;
use App\Models\CountryTranslation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['citiesUpdated', 'deleted'])]
class Cities extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use DropdownConstructTrait;
    use WithPagination;

    public string $searchCountry = '';

    public string $searchParent = '';

    public function rules(): array
    {
        return [
            'form.name' => 'required|string|min:2',
            'form.country_id' => 'required|integer|min:1|exists:countries,id',
            'form.parent_id' => 'nullable|integer|exists:cities,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('Name'),
            'form.country_id' => __('Country'),
            'form.parent_id' => __('Parent'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'country_id' => null,
            'parent_id' => null,
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? City::with(['country.currentCountryTranslations:country_id,id,title', 'parent:id,name'])->findOrFail($id)
            : null;

        $this->form = $this->formDefaults();

        if ($this->model) {
            $this->form['id'] = $this->model->id;
            $this->form['name'] = $this->model->name;
            $this->form['country_id'] = $this->model->country_id;
            $this->form['parent_id'] = $this->model->parent_id;
        }

        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = City::findOrFail($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->form['parent_id'] = $this->form['parent_id'] ?? null;

        $this->model
            ? $this->model->update($this->form)
            : City::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('citiesUpdated');
        $this->closeCrud();
        $this->resetPage();
    }

    public function render()
    {
        $cities = City::with('country.currentCountryTranslations')->paginate(20);

        return view('admin::livewire.admin.cities', compact('cities'));
    }

    public function countryOptions(): array
    {
        $base = CountryTranslation::query()
            ->selectRaw('country_id as id, title as label')
            ->where('locale', config('app.locale'))
            ->orderBy('title');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'title',
            searchTerm: $this->dropdownSearch('searchCountry'),
            selectedId: data_get($this->form, 'country_id'),
            limit: 100
        );
    }

    public function parentCityOptions(): array
    {
        $base = City::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchParent'),
            selectedId: data_get($this->form, 'parent_id'),
            limit: 100
        );
    }
}
