<?php

namespace App\Livewire\Filter;

use App\Models\City;
use App\Models\Rank;
use App\Models\Award;
use App\Models\Country;
use Livewire\Component;
use App\Models\Position;
use App\Models\Structure;
use App\Models\Punishment;
use Livewire\Attributes\On; 
use App\Models\EducationDegree;
use Illuminate\Support\Facades\DB;
use App\Models\EducationalInstitution;

class Detail extends Component
{
    public array $filter = [];

    public $structureId,$structureName,$searchStructure;
    public $positionId,$positionName,$searchPosition;
    public $nationalityId,$nationalityName,$searchNationality;
    public $bornCountryId,$bornCountryName,$searchPreviousNationality;
    public $bornCityId,$bornCityName,$searchCity;
    public $rankId,$rankName,$searchRank;
    public $educationDegreeId,$educationDegreeName,$searchEducationDegree;
    public $institutionId,$institutionName,$searchInstitution;
    public $awardId,$awardName,$searchAward;
    public $punishmentId,$punishmentName,$searchPunishment;

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

    public function updatedFilter($value,$name)
    {
       if(empty($value) && $name != 'is_married')
       {
            unset($this->filter[$name]);
       }
    }

    public function search()
    {
        //datani gondermek birbasa query stringe
        $this->dispatch('filterSelected' , $this->filter);
    }

    public function setData($model,$key,$content,$name,$id)
    {
        $this->{$content.'Id'} = $id;
        $this->{$content.'Name'} = $name;
        if(!empty($id))
        {
            $this->{$model}[$key] = $id; 
        }
        else
        {
            unset($this->{$model}[$key]);
        }
    }

    public function mount()
    {
        $this->structureName = $this->positionName = $this->nationalityName = $this->bornCountryName = $this->bornCityName = $this->rankName = $this->institutionName = $this->educationDegreeName = $this->awardName = $this->punishmentName ='---';
    }

    public function render()
    {
        $structures = Structure::when(!empty($this->searchStructure),function($q){
            $q->where('name','LIKE',"%{$this->searchStructure}%");
                })
                ->get();

        $positions = Position::when(!empty($this->searchPosition),function($q){
                    $q->where('name','LIKE',"%{$this->searchPosition}%");
                })
                ->get();

        $nationalities = Country::whereHas('currentCountryTranslations',function($query){
                    $query->when(!empty($this->searchNationality),function($q){
                        $q->where('title','LIKE',"%{$this->searchNationality}%");
                    })
                    ->when(!empty($this->searchPreviousNationality),function($q){
                        $q->where('title','LIKE',"%{$this->searchPreviousNationality}%");
                    });
                })
                ->with('currentCountryTranslations')
                ->get()
                ->sortBy('currentCountryTranslations.title')
                ->all();
            
        $cities = City::select('id','name','country_id')
                ->when(!empty($this->searchCity),function($q){
                    $q->where('name','LIKE',"%{$this->searchCity}%");
                })
                ->when(!empty($this->bornCountryId),function($q){
                    $q->where('country_id',$this->bornCountryId);
                })
                ->get();

        $rankModel = Rank::query()
                ->when(!empty($this->searchRank),function($q){
                    $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchRank}%");
                })
                ->where('is_active',1)
                ->get();

        
        $institutions = EducationalInstitution::when(!empty($this->searchInstitution),function($q){
                    $q->where('name','LIKE',"%{$this->searchInstitution}%");
                })
                ->get();        

        $education_degrees = EducationDegree::select('id',DB::raw('title_'.config('app.locale').' as title'))
                ->when(!empty($this->searchEducationDegree),function($q){
                    $q->where('title_'.config('app.locale'),'LIKE',"%{$this->searchEducationDegree}%");
                })
                ->get();

        $awardModel = Award::when(!empty($this->searchAward),function($q){
                    $q->where('name','LIKE',"%{$this->searchAward}%");
                })
                ->get();
        
        $punishmentModel = Punishment::when(!empty($this->searchPunishment),function($q){
                    $q->where('name','LIKE',"%{$this->searchPunishment}%");
                })
                ->criminalType('other')
                ->orderBy('name')
                ->get();

        return view('livewire.filter.detail',compact('structures','positions','nationalities','cities','rankModel','institutions','education_degrees','awardModel','punishmentModel'));
    }
}
