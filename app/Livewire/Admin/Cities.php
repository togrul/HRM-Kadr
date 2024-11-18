<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\SelectListTrait;
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
    use SelectListTrait;
    use WithPagination;

    public string $searchCountry;

    public string $searchParent;

    public function rules(): array
    {
        return [
            'form.name' => 'required|string|min:2',
            'form.country_id.id' => 'required|integer|min:1|exists:countries,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('Name'),
            'form.country_id.id' => __('Country'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? City::with(['country.currentCountryTranslations:country_id,id,title', 'parent:id,name'])->findOrFail($id)
            : null;

        if ($this->model) {
            $this->form = $this->model->toArray();
            unset($this->form['country']['current_country_translations']['country_id']);
            $this->form['country_id'] = data_get($this->form, 'country.current_country_translations');
//            $this->form['country_id'] = fluent($this->form)->get('country.current_country_translations');
            $this->form['parent_id'] = $this->form['parent'] ?? [
                'id' => null,
                'name' => '---',
            ];

            unset($this->form['country']);
            unset($this->form['parent']);
        } else {
            $this->form = [];
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

        $this->form['country_id'] = $this->form['country_id']['id'];
        $this->form['parent_id'] = array_key_exists('parent_id', $this->form) ? $this->form['parent_id']['id'] : null;

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
        $countries = CountryTranslation::query()
            ->when(! empty($this->searchCountry), function ($q) {
                $q->where('title', 'LIKE', "%$this->searchCountry%");
            })
            ->where('locale', config('app.locale'))
            ->get();

        $all_cities = City::query()
            ->when(! empty($this->searchParent), function ($query) {
                $query->where('name', 'LIKE', "%$this->searchParent%");
            })
            ->get();

        $cities = City::with('country.currentCountryTranslations')->paginate(20);

        return view('livewire.admin.cities', compact('cities', 'all_cities', 'countries'));
    }
}
