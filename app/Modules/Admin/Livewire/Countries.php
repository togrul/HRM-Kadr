<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Models\Country;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['countryUpdated', 'deleted'])]
class Countries extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use WithPagination;

    public $hasError = false;

    public string $selectedLocale;

    public function rules(): array
    {
        return [
            'form.code' => 'required|string|min:2',
            'form.country_translations.title' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.code' => __('Code'),
            'form.country_translations.title' => __('Name'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id ? $this->checkCountryExist($id) : null;

        if ($this->model) {
            $this->form = $this->model->toArray();
            $this->form['country_translations'] = $this->form['country_translations'][0];
        }
        else {
            $this->form = [];
        }
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id && ($this->model = $this->checkCountryExist($id))) {
            $this->callDeletePromptSwal();
        }
    }

    public function setLocale(string $lang): void
    {
        $this->selectedLocale = $lang;
        $this->closeCrud();
    }

    private function checkCountryExist($id)
    {
        return Country::with(['countryTranslations' => function ($query) {
            $query->where('locale', $this->selectedLocale);
        }])
            ->find($id);
    }

    public function store(): void
    {
        $this->hasError = false;
        $this->validate();

        $this->form['country_translations']['locale'] = $this->selectedLocale;

        $this->model ? $this->updateCountry() : $this->createCountry();

        $this->hasError ? $this->callWarningSwal() : $this->callSuccessSwal();
        if ($this->hasError) {
            $this->callWarningSwal();
        } else {
            $this->callSuccessSwal();
            $this->dispatch('countryUpdated');
            $this->closeCrud();
        }
    }

    private function createCountry(): void
    {
        // Begin transaction
        DB::transaction(function () {
            // Determine the country ID
            $countryId = array_key_exists('id', $this->form) ? (int) $this->form['id'] : $this->generateNextCountryId();

            $checkData = $this->checkCountryExist($countryId);
            if ($checkData) {
                if (empty($checkData->countryTranslations)) {
                    $this->hasError = true;
                } else {
                    $countryModel = $checkData;
                }
            } else {
                $countryModel = Country::create([
                    'id' => $countryId,
                    'code' => $this->form['code'],
                ]);
            }
            if (! $this->hasError) {
                $countryModel->countryTranslations()->create($this->form['country_translations']);
            }
        });
    }

    /**
     * Generate the next country ID based on the maximum ID in the database.
     */
    private function generateNextCountryId(): int
    {
        return (Country::max('id') ?? 0) + 1;
    }

    private function updateCountry(): void
    {
        $this->model->load('countryTranslations');

        DB::transaction(function () {
            $countryId = (int) $this->form['id'];
            $this->form['country_translations']['country_id'] = $countryId;
            unset($this->form['country_translations']['id']);
            $this->model->update([
                'id' => $countryId,
                'code' => $this->form['code'],
            ]);
            $this->model->countryTranslations()->where('locale', $this->form['country_translations']['locale'])->update($this->form['country_translations']);
        });
    }

    public function mount(): void
    {
        $this->selectedLocale = config('app.locale');
    }

    public function render()
    {
        $countries = Country::whereHas('countryTranslations', function ($query) {
            $query->where('locale', $this->selectedLocale);
        })
            ->with(['countryTranslations' => function ($query) {
                $query->where('locale', $this->selectedLocale);
            }])
            ->paginate(20);

        $countries = $this->decorateCountries($countries);

        return view('admin::livewire.admin.countries', compact('countries'));
    }

    protected function decorateCountries(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Country $country) {
                $translation = $country->countryTranslations->first();
                $country->locale_code = $translation?->locale ?? '';
                $country->locale_title = $translation?->title ?? '';

                return $country;
            })
        );

        return $paginated;
    }
}
