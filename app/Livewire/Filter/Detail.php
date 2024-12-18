<?php

namespace App\Livewire\Filter;

use App\Models\Award;
use App\Models\City;
use App\Models\Country;
use App\Models\EducationalInstitution;
use App\Models\EducationDegree;
use App\Models\Position;
use App\Models\Punishment;
use App\Models\Rank;
use App\Models\Structure;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;


class Detail extends Component
{
    public array $filter = [];

    public $structureId;

    public $structureName;

    public $searchStructure;

    public $positionId;

    public $positionName;

    public $searchPosition;

    public $nationalityId;

    public $nationalityName;

    public $searchNationality;

    public $bornCountryId;

    public $bornCountryName;

    public $searchPreviousNationality;

    public $bornCityId;

    public $bornCityName;

    public $searchCity;

    public $rankId;

    public $rankName;

    public $searchRank;

    public $educationDegreeId;

    public $educationDegreeName;

    public $searchEducationDegree;

    public $institutionId;

    public $institutionName;

    public $searchInstitution;

    public $awardId;

    public $awardName;

    public $searchAward;

    public $punishmentId;

    public $punishmentName;

    public $searchPunishment;

    #[On('filterResetted')]
    public function filterResetted()
    {
        $this->filter = [];
        $this->mount();
    }

    #[On('setOpenFilter')]
    public function setOpenFilter()
    {
        $this->dispatch('openFilterWasSet');
    }

    public function updatedFilter($value, $name)
    {
        if (empty($value) && $name != 'is_married') {
            unset($this->filter[$name]);
        }
    }

    public function search()
    {
        //datani gondermek birbasa query stringe
        $this->dispatch('filterSelected', $this->filter);
    }

    public function setData($model, $key, $content, $name, $id)
    {
        $this->{$content.'Id'} = $id;
        $this->{$content.'Name'} = $name;
        if (! empty($id)) {
            $this->{$model}[$key] = $id;
        } else {
            unset($this->{$model}[$key]);
        }
    }

    public function mount()
    {
        $this->structureName = $this->positionName = $this->nationalityName = $this->bornCountryName = $this->bornCityName = $this->rankName = $this->institutionName = $this->educationDegreeName = $this->awardName = $this->punishmentName = '---';
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div>
          <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="80px" height="80px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve">
            <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946
              s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634
              c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/>
            <path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0
              C22.32,8.481,24.301,9.057,26.013,10.047z">
              <animateTransform attributeType="xml"
                attributeName="transform"
                type="rotate"
                from="0 20 20"
                to="360 20 20"
                dur="0.9s"
                repeatCount="indefinite"/>
            </path>
          </svg>
        </div>
        HTML;
    }

    public function render()
    {
        $structures = Structure::when(! empty($this->searchStructure), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchStructure}%");
        })
            ->ordered()
            ->get();

        $positions = Position::when(! empty($this->searchPosition), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchPosition}%");
        })
            ->get();

        $nationalities = Country::whereHas('currentCountryTranslations', function ($query) {
            $query->when(! empty($this->searchNationality), function ($q) {
                $q->where('title', 'LIKE', "%{$this->searchNationality}%");
            })
                ->when(! empty($this->searchPreviousNationality), function ($q) {
                    $q->where('title', 'LIKE', "%{$this->searchPreviousNationality}%");
                });
        })
            ->with('currentCountryTranslations')
            ->get()
            ->sortBy('currentCountryTranslations.title')
            ->all();

        $cities = City::select('id', 'name', 'country_id')
            ->when(! empty($this->searchCity), function ($q) {
                $q->where('name', 'LIKE', "%{$this->searchCity}%");
            })
            ->when(! empty($this->bornCountryId), function ($q) {
                $q->where('country_id', $this->bornCountryId);
            })
            ->get();

        $rankModel = Rank::query()
            ->when(! empty($this->searchRank), function ($q) {
                $q->where('name_'.config('app.locale'), 'LIKE', "%{$this->searchRank}%");
            })
            ->where('is_active', 1)
            ->get();

        $institutions = EducationalInstitution::when(! empty($this->searchInstitution), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchInstitution}%");
        })
            ->get();

        $education_degrees = EducationDegree::select('id', DB::raw('title_'.config('app.locale').' as title'))
            ->when(! empty($this->searchEducationDegree), function ($q) {
                $q->where('title_'.config('app.locale'), 'LIKE', "%{$this->searchEducationDegree}%");
            })
            ->get();

        $awardModel = Award::when(! empty($this->searchAward), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchAward}%");
        })
            ->get();

        $punishmentModel = Punishment::when(! empty($this->searchPunishment), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchPunishment}%");
        })
            ->criminalType('other')
            ->orderBy('name')
            ->get();

        return view('livewire.filter.detail', compact('structures', 'positions', 'nationalities', 'cities', 'rankModel', 'institutions', 'education_degrees', 'awardModel', 'punishmentModel'));
    }
}
