<?php

namespace App\Livewire\Traits;
use App\Models\City;
use App\Models\Rank;
use App\Models\Award;
use App\Models\Country;
use App\Models\Kinship;
use App\Models\Language;
use App\Models\Position;
use App\Models\WorkNorm;
use App\Models\Structure;
use App\Models\Disability;
use App\Models\Punishment;
use Illuminate\Support\Arr;
use Livewire\Attributes\On; 
use App\Models\EducationForm;
use App\Models\EducationType;
use Livewire\WithFileUploads;
use App\Models\EducationDegree;
use App\Enums\KnowledgeStatusEnum;
use Illuminate\Support\Facades\DB;
use App\Livewire\Traits\Step1Trait;
use App\Livewire\Traits\Step2Trait;
use App\Livewire\Traits\Step3Trait;
use App\Livewire\Traits\Step4Trait;
use App\Livewire\Traits\Step5Trait;
use App\Livewire\Traits\Step6Trait;
use App\Livewire\Traits\Step7Trait;
use App\Livewire\Traits\Step8Trait;
use App\Models\EducationDocumentType;
use App\Models\EducationalInstitution;
use App\Models\ScientificDegreeAndName;

trait PersonnelCrud
{
    use WithFileUploads,Step1Trait,Step2Trait,Step3Trait,Step4Trait,Step5Trait,Step6Trait,Step7Trait,Step8Trait;
    public $title; 

    public $step;
    public array $completedSteps;

    public function validationRules() 
    {
        return [
           1 => [
            'personnel.tabel_no' => 'required|min:3|unique:personnels,tabel_no'. (!empty($this->personnelModel) ? ','.$this->updatePersonnel['id'] : ''),
            'personnel.name' => 'required|min:3',
            'personnel.surname' => 'required|min:3',
            'personnel.patronymic' => 'required|min:3',
            'personnel.birthdate' => 'required|date',
            'personnel.previous_name'=> $this->personnel['has_changed_initials'] ? 'required|min:3' : '',
            'personnel.previous_surname'=> $this->personnel['has_changed_initials'] ? 'required|min:3' : '',
            'personnel.previous_patronymic'=> $this->personnel['has_changed_initials'] ? 'required|min:3' : '',
            'personnel.gender' => 'required|int',
            'personnel.initials_changed_date' =>  $this->personnel['has_changed_initials'] ?'required|date' : '',
            'personnel.initials_change_reason'=> $this->personnel['has_changed_initials'] ? 'required|min:3' : '',
            'personnel.nationality_id.id' => 'required|int|exists:countries,id',
            'personnel.previous_nationality_id.id' => $this->personnel['has_changed_nationality'] ? 'required|int|exists:countries,id' : '',
            'personnel.nationality_changed_date' => $this->personnel['has_changed_nationality'] ? 'required|date' : '',
            'personnel.nationality_change_reason' => $this->personnel['has_changed_nationality'] ? 'required|min:3' : '',
            'personnel.phone' => ['required','min:7'],
            'personnel.mobile' => ['required','min:7'],
            'personnel.email' => 'required|email',
            'personnel.pin' => 'required|min:7|max:7',
            'personnel.residental_address' => 'required|min:3',
            'personnel.registered_address' => 'required|min:3',
            'personnel.education_degree_id.id' => 'required|int|exists:education_degrees,id',
            'personnel.structure_id.id' => 'required|int',
            'personnel.position_id.id' => 'required|int',
            'personnel.work_norm_id.id' => 'required|int|exists:work_norms,id',
            'personnel.join_work_date' => 'required|date',
            'personnel.disability_id.id' => $this->isDisability ? 'required|int|exists:disabilities,id' : '',
            'personnel.disability_given_date' => $this->isDisability ? 'required|date' : '',
           ],
           2 => [
            'document.pin' => 'required|min:7',
            'document.nationality_id.id' => 'required|int|exists:countries,id',
            'document.series' => 'required|min:1',
            'document.number' => 'required|int',
            'document.born_country_id.id' => 'required|int|exists:countries,id',
            'document.born_city_id.id' => 'required|int|exists:cities,id',
            'document.is_married' => 'required|boolean',
            'document.height' => 'required|int',
           ],
           3 => [
            'education.educational_institution_id.id' => 'required|int|exists:educational_institutions,id',
            'education.education_form_id.id' => 'required|int|exists:education_forms,id',
            'education.education_language' => 'required|min:2',
            'education.specialty' => 'required|min:2',
            'education.admission_year' => 'required|int',
            'education.graduated_year' => 'required|int|gt:education.admission_year',
            'education.profession_by_document' => 'required|min:2',
            'education.diplom_serie' => 'required|min:1',
            'education.diplom_no' => 'required|int',
            'education.diplom_given_date' => 'required|date',
            'extra_education.education_type_id.id' => $this->hasExtraEducation ? 'required|int|exists:education_types,id' : '',
            'extra_education.educational_institution_id.id' => $this->hasExtraEducation ? 'required|int|exists:educational_institutions,id' : '',
            'extra_education.education_form_id.id' => $this->hasExtraEducation ? 'required|int|exists:education_forms,id' : '',
            'extra_education.name' => $this->hasExtraEducation ? 'required|min:2' : '',
            'extra_education.shortname' => $this->hasExtraEducation ? 'required|min:2' : '',
            'extra_education.education_language' => $this->hasExtraEducation ? 'required|min:2' : '',
            'extra_education.education_program_name' => $this->hasExtraEducation ? 'required|min:2' : '',
            'extra_education.admission_year' =>  $this->hasExtraEducation ? 'required|int' : '',
            'extra_education.graduated_year' =>   $this->hasExtraEducation ?'required|int|gt:extra_education.admission_year' : '',
            'extra_education.education_document_type_id.id' => $this->hasExtraEducation ? 'required|int|exists:education_document_types,id' : '',
            'extra_education.diplom_serie' => $this->hasExtraEducation ? 'required|min:1' : '',
            'extra_education.diplom_no' => $this->hasExtraEducation ? 'required|int|unique:personnel_extra_education,diplom_no' : '',
            'extra_education.diplom_given_date' => $this->hasExtraEducation ? 'required|date' : '',
           ],
           4 => [
            'labor_activities.company_name' => 'required|min:2',
            'labor_activities.position' => 'required|min:2',
            'labor_activities.join_date' => 'required|date',
            'labor_activities.leave_date' => 'required|date',
            'ranks.rank_id.id' => $this->isAddedRank ? 'required|int|exists:ranks,id' : '',
            'ranks.name' => $this->isAddedRank ? 'required|min:2' : '',
            'ranks.given_date' => $this->isAddedRank ? 'required|date' : '',
           ],
           5 => [
            'military.rank_id.id' => 'required|int|exists:ranks,id',
            'military.attitude_to_military_service' => 'required|min:2',
            'military.given_date' => 'required|date',
           ],
           6 => [
            'award.award_id.id' => 'required|int|exists:awards,id',
            'award.reason' => 'required|min:2',
            'award.given_date' => 'required|date',
            'punishment.punishment_id.id' => 'required|int|exists:punishments,id',
            'punishment.reason' => 'required|min:2',
            'punishment.given_date' => 'required|date',
            'criminal.criminal_id.id' => 'required|int|exists:punishments,id',
            'criminal.reason' => 'required|min:2',
            'criminal.given_date' => 'required|date',
           ],
           7 => [
            'kinship.kinship_id.id' => 'required|int|exists:kinships,id',
            'kinship.fullname' => 'required|min:2',
            'kinship.birthdate' => 'required|date',
            'kinship.registered_address' => 'required|min:2',
            'kinship.residental_address' => 'required|min:2'
           ],
           8 => [
            'language.language_id.id' => 'required|int|exists:languages,id',
            'language.knowledge_status' => 'required',
            'event.event_type' => 'required|min:2',
            'event.event_name' => 'required|min:2',
            'event.event_date' => 'required|date',
            'degree.degree_and_name_id.id' => 'required|int|exists:education_degrees,id',
            'degree.science' => 'required|min:2',
            'degree.given_date' => 'required|date',
            'degree.subject' => 'required|min:2',
            'degree.edu_doc_type_id.id' => 'required|int|exists:education_document_types,id',
            'degree.diplom_serie' => 'required|min:1',
            'degree.diplom_no' => 'required|int',
            'degree.diplom_given_date' => 'required|date',
            'degree.document_issued_by' => 'required|min:2',
           ]
        ];
    }
 
    protected function validationAttributes()
    {
        return [
            'personnel.tabel_no'=> __('Tabel no'),
            'personnel.name'=> __('Name'),
            'personnel.surname'=> __('Surname'),
            'personnel.patronymic'=> __('Patronymic'),
            'personnel.birthdate'=> __('Birthdate'),
            'personnel.previous_name'=> __('Previous name'),
            'personnel.previous_surname'=> __('Previous surname'),
            'personnel.previous_patronymic'=> __('Previous patronymic'),
            'personnel.gender'=> __('Gender'),
            'personnel.initials_changed_date'=> __('Change date'),
            'personnel.initials_change_reason'=> __('Change reason'),
            'personnel.nationality_id.id'=> __('Nationality'),
            'personnel.previous_nationality_id.id'=> __('Previous nationality'),
            'personnel.nationality_changed_date'=> __('Nationality change date'),
            'personnel.nationality_change_reason'=> __('Nationality change reason'),
            'personnel.phone'=> __('Phone'),
            'personnel.mobile'=> __('Mobile'),
            'personnel.email'=> __('Email'),
            'personnel.pin'=> __('PIN'),
            'personnel.residental_address'=> __('Residental address'),
            'personnel.registered_address'=> __('Registered address'),
            'personnel.education_degree_id.id'=> __('Education degree'),
            'personnel.structure_id.id'=> __('Structure'),
            'personnel.position_id.id'=> __('Position'),
            'personnel.work_norm_id.id'=> __('Work norm'),
            'personnel.join_work_date'=> __('Join work date'),
            'personnel.disability_id.id'=> __('Disability'),
            'personnel.disability_given_date'=> __('Disability given date'),
            'document.body.pin' => __('Pin'),
            'document.nationality_id.id' => __('Nationality'),
            'document.series' => __('Series'),
            'document.number' => __('Document number'),
            'document.born_country_id.id' => __('Born country'),
            'document.born_city_id.id' => __('City'),
            'document.is_married' => __('Family status'),
            'document.height' => __('Height'),
            'education.educational_institution_id.id' => __('Institution'),
            'education.education_form_id.id' => __('Education form'),
            'education.education_language' => __('Education language'),
            'education.specialty' => __('Specialty'),
            'education.admission_year' => __('Admission year'),
            'education.graduated_year' => __('Graduated year'),
            'education.profession_by_document' => __('Profession'),
            'education.diplom_serie' => __('Diplom serie'),
            'education.diplom_no' => __('Diplom no'),
            'education.diplom_given_date' => __('Diplom given date'),
            'extra_education.education_type_id.id' => __('Education type'),
            'extra_education.educational_institution_id.id' => __('Institution'),
            'extra_education.education_form_id.id' => __('Education form'),
            'extra_education.name' => __('Name'),
            'extra_education.shortname' => __('Shortname'),
            'extra_education.education_language' => __('Education language'),
            'extra_education.education_program_name' => __('Program name'),
            'extra_education.admission_year' => __('Admission year'),
            'extra_education.graduated_year' => __('Graduated year'),
            'extra_education.education_document_type_id.id' => __('Document type'),
            'extra_education.diplom_serie' => __('Diplom serie'),
            'extra_education.diplom_no' => __('Diplom no'),
            'extra_education.diplom_given_date' => __('Diplom given date'),
            'labor_activities.company_name' => __('Company'),
            'labor_activities.position' => __('Position'),
            'labor_activities.join_date' => __('Join date'),
            'labor_activities.leave_date' => __('Leave date'),
            'ranks.rank_id.id' => __('Rank'),
            'ranks.name' => __('Name'),
            'ranks.given_date' => __('Given date'),
            'military.rank_id.id' => __('Rank'),
            'military.attitude_to_military_service' => __('Attitude'),
            'military.given_date' => __('Given date'),
            'award.award_id.id' => __('Award'),
            'award.reason' => __('Reason'),
            'award.given_date' => __('Given date'),
            'punishment.punishment_id.id' => __('Punishment'),
            'punishment.reason' => __('Reason'),
            'punishment.given_date' => __('Given date'),
            'criminal.criminal_id.id' => __('Criminal'),
            'criminal.reason' => __('Reason'),
            'criminal.given_date' => __('Given date'),
            'kinship.kinship_id.id' => __('Kinship'),
            'kinship.fullname' => __('Fullname'),
            'kinship.birthdate' => __('Birthdate'),
            'kinship.registered_address' => __('Registered address'),
            'kinship.residental_address' => __('Residental address'),
            'language.language_id.id' => __('Language'),
            'language.knowledge_status' => __('Knowledge'),
            'event.event_type' =>  __('Event type'),
            'event.event_name' => __('Event name'),
            'event.event_date' => __('Event date'),
            'degree.degree_and_name_id.id' => __('Degree'),
            'degree.science' => __('Science'),
            'degree.given_date' => __('Given date'),
            'degree.subject' => __('Subject'),
            'degree.edu_doc_type_id.id' => __('Document type'),
            'degree.diplom_serie' => __('Diplom serie'),
            'degree.diplom_no' => __('Diplom number'),
            'degree.diplom_given_date' => __('Given date'),
            'degree.document_issued_by' => __('Issued by'),
        ];
    }

    public function previousStep()
    {
        if($this->step > 1)
            $this->step --;
        else
            $this->step = 1;
        
    }
   
    public function exceptArray($arrayKey)
    {
        $filtered = array_filter($this->validationRules()[$this->step], function ($key) use($arrayKey) {
            return strpos($key, $arrayKey) === 0;
        }, ARRAY_FILTER_USE_KEY);

        return Arr::except($this->validationRules()[$this->step],array_keys($filtered));
    }

  
    public function selectStep($step)
    {
        if($this->step == 1)
          $this->validate($this->validationRules()[$this->step]);
        $this->step = $step;
    }

    protected function completeStep()
    {
        $stepName = match($this->step)
        {
            1 => 'personnel',
            2 => 'document',
            3 => 'education'
        };
       if(count($this->{$stepName}) > 0)
       {
            $this->validate($this->validationRules()[$this->step]);
            !in_array($stepName,$this->completedSteps)  &&  $this->completedSteps[] = $stepName;
       }
    }

    public function nextStep()
    {
        $this->isAddedRank = false;
        if(($this->step == 3 && !empty($this->extra_education_list) ) || ($this->step == 4 && !empty($this->labor_activities_list)))
        {
            $exceptValidation = match($this->step)
            {
                3 => 'extra_education',
                4 => 'labor_activities',
                default => ''
            };
            $validator = $this->exceptArray($exceptValidation);
            !empty($validator) && $this->validate($validator);
        }
        else
        {
            $this->validate($this->validationRules()[$this->step]);
        }

        if($this->step == 2 || $this->step == 3)
        {
            $this->completeStep();
        }

        $this->step++;
       
    }

    public function setData($model,$key,$content,$name,$id)
    {
        $this->{$content.'Id'} = $id;
        $this->{$content.'Name'} = $name;
        
        if(!empty($id))
        {
            if(array_key_exists($key,$this->{$model}))
            {
                $this->{$model}[$key] =  [
                    'id' => $id,
                    'name' => $name
                ];
            }
            else
            {
                $this->{$model} += [
                    $key => [
                        'id' => $id,
                        'name' => $name
                    ]
                ];
            }
           
            if($model == 'extra_education' && $key == 'educational_institution_id')
            {
                $this->extra_education['name'] = $name;
                $this->extra_education['shortname'] = EducationalInstitution::find($id)->value('shortname');
            }
        }
        else
        {
            unset($this->{$model}[$key]);
            if($model == 'extra_education' && $key == 'educational_institution_id')
            {
                unset($this->{$model}['name']);
                unset($this->{$model}['shortname']);
            }
        }

        if($content == 'nationality' || $content == 'previousNationality') 
            $this->searchPreviousNationality = $this->searchNationality ='';
        if($content == 'institution' || $content == 'extraInstitution') 
            $this->searchExtraInstitution = $this->searchInstitution ='';
        if($content == 'educationForm' || $content == 'extraEducationForm') 
            $this->searchExtraEducationForm = $this->searchEducationForm ='';
        if($content == 'ranks' || $content == 'militaryRank') 
            $this->searchMilitaryRank = $this->searchRank ='';
    }

    protected function modfiyArray($array)
    {
        $filteredArray = array_filter($array, function($key) {

           return stripos($key, "_id") !== false;
       
       }, ARRAY_FILTER_USE_KEY);

       foreach($filteredArray as $key => $value)
       {
            unset($array[$key]);
            $array[$key] = $value['id'];
       }

       return $array;
    }

    public function render()
    {
        $steps = [
            1 => __('Personal Information'),
            2 => __('ID document'),
            3 => __('Education'),
            4 => __('Labor activities'),
            5 => __('Military'),
            6 => __('Awards and punishments'),
            7 => __('Kinships'),
            8 => __('Other')
        ];

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
                        ->sortBy('currentCountryTranslations.title')->all();
        
        $education_degrees = EducationDegree::select('id',DB::raw('title_'.config('app.locale').' as title'))
                            ->when(!empty($this->searchEducationDegree),function($q){
                                $q->where('title_'.config('app.locale'),'LIKE',"%{$this->searchEducationDegree}%");
                            })
                            ->get();

        $structures = Structure::when(!empty($this->searchStructure),function($q){
                            $q->where('name','LIKE',"%{$this->searchStructure}%");
                        })
                        ->get();
        
        $positions = Position::when(!empty($this->searchPosition),function($q){
                            $q->where('name','LIKE',"%{$this->searchPosition}%");
                        })
                        ->get();

        $work_norms = WorkNorm::select('id',DB::raw('name_'.config('app.locale').' as name'))
                        ->when(!empty($this->searchWorkNorm),function($q){
                            $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchWorkNorm}%");
                        })
                        ->get();

        $disabilities = $this->isDisability 
                ? Disability::when(!empty($this->searchDisability),function($q){
                    $q->where('name','LIKE',"%{$this->searchDisability}%");
                })
                ->get()
                : [];

        $institutions = EducationalInstitution::when(!empty($this->searchInstitution),function($q){
                            $q->where('name','LIKE',"%{$this->searchInstitution}%");
                        })
                        ->when(!empty($this->searchExtraInstitution),function($q){
                            $q->where('name','LIKE',"%{$this->searchExtraInstitution}%");
                        })
                        ->get();        
        
        $education_forms = EducationForm::select('id',DB::raw('name_'.config('app.locale').' as name'))
                        ->when(!empty($this->searchEducationForm),function($q){
                            $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchEducationForm}%");
                        })
                        ->when(!empty($this->searchExtraEducationForm),function($q){
                            $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchExtraEducationForm}%");
                        })
                        ->get();

        $education_types = EducationType::when(!empty($this->searchEducationType),function($q){
                        $q->where('name','LIKE',"%{$this->searchEducationType}%");
                    })
                    ->get();


        $document_types = EducationDocumentType::when(!empty($this->searchDocumentTyoe),function($q){
                        $q->where('name','LIKE',"%{$this->searchDocumentTyoe}%");
                    })
                    ->get();

        $rankModel = Rank::select('id',DB::raw('name_'.config('app.locale').' as name'),'is_active')
                    ->when(!empty($this->searchRank),function($q){
                        $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchRank}%");
                    })
                    ->when(!empty($this->searchMilitaryRank),function($q){
                        $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchMilitaryRank}%");
                    })
                    ->where('is_active',1)
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

        $criminalModel = Punishment::when(!empty($this->searchCriminal),function($q){
                    $q->where('name','LIKE',"%{$this->searchCriminal}%");
                })
                ->criminalType('criminal')
                ->orderBy('name')
                ->get();

        $kinshipModel = Kinship::select('id',DB::raw('name_'.config('app.locale').' as name'),'is_active')
                    ->when(!empty($this->searchKinship),function($q){
                        $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchKinship}%");
                    })
                    ->where('is_active',1)
                    ->get();

        $languageModel = Language::all();

        $knowledges = KnowledgeStatusEnum::values();

        $educationDocs  =  EducationDocumentType::all();

        $cities = City::select('id','name','country_id')
            ->when(!empty($this->searchCity),function($q){
                $q->where('name','LIKE',"%{$this->searchCity}%");
            })
            ->when(!empty($this->documentBornCountryId),function($q){
                $q->where('country_id',$this->documentBornCountryId);
            })
            ->get();

        $degrees = ScientificDegreeAndName::all();

        $view_name =  !empty($this->personnelModel) ? 'livewire.personnel.edit-personnel' : 'livewire.personnel.add-personnel';

        return view($view_name,compact(
            'steps',
            'nationalities',
            'education_degrees',
            'structures',
            'positions',
            'work_norms',
            'disabilities',
            'institutions',
            'education_forms',
            'education_types',
            'document_types',
            'rankModel',
            'awardModel',
            'punishmentModel',
            'criminalModel',
            'kinshipModel',
            'languageModel',
            'knowledges',
            'educationDocs',
            'cities',
            'degrees'
        ));
    }
}