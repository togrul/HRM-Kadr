<?php

namespace App\Livewire\Personnel;

use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\PersonalInformationForm;
use App\Livewire\Traits\PersonnelCrud;
use App\Livewire\Traits\RelationCruds\RelationCrudTrait;
use App\Models\Personnel;
use App\Models\PersonnelAward;
use App\Models\PersonnelCard;
use App\Models\PersonnelCriminal;
use App\Models\PersonnelEducation;
use App\Models\PersonnelElectedElectoral;
use App\Models\PersonnelExtraEducation;
use App\Models\PersonnelIdentityDocument;
use App\Models\PersonnelInjury;
use App\Models\PersonnelKinship;
use App\Models\PersonnelLaborActivity;
use App\Models\PersonnelMilitaryService;
use App\Models\PersonnelParticipationEvent;
use App\Models\PersonnelPassports;
use App\Models\PersonnelPunishment;
use App\Models\PersonnelRank;
use App\Models\PersonnelScientificDegreeAndName;
use App\Models\PersonnelTakenCaptive;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditPersonnel extends Component
{
    use PersonnelCrud;
    use RelationCrudTrait;

    public PersonalInformationForm $personalForm;
    public DocumentForm $documentForm;
    public EducationForm $educationForm;
    public LaborActivityForm $laborActivityForm;

    public $updatePersonnel;

    public $personnelModelData;

    public $personnelModel;

    public function mount()
    {
        $this->authorize('edit-personnels', $this->personnelModel);
        $this->title = __('Edit personnel');
        $this->step = 1;
        $this->personnelModelData = Personnel::with([
            'nationality',
            'previousNationality',
            'educationDegree',
            'structure',
            'position',
            'disability',
            'workNorm',
            'awards',
            //            'criminals',
            'idDocuments',
            'education',
            'extraEducations',
            'foreignLanguages',
            'kinships',
            'laborActivities',
            'military',
            'participations',
            'punishments',
            'ranks',
            'degreeAndNames',
            'socialOrigin',
        ])
            ->findOrFail($this->personnelModel);

        if (isset($this->personalForm)) {
            $this->personalForm->fillFromModel($this->personnelModelData);
            $this->syncArraysFromPersonalForm();
        }

        if (isset($this->documentForm)) {
            $this->documentForm->fillFromModel($this->personnelModelData);
            $this->syncArraysFromDocumentForm();
        } else {
            $this->hydrateDocumentFormFromArrays();
        }

        if (isset($this->educationForm)) {
            $this->educationForm->fillFromModel($this->personnelModelData);
            $this->syncArraysFromEducationForm();
        } else {
            $this->hydrateEducationFormFromArrays();
        }

        if (isset($this->laborActivityForm)) {
            $this->laborActivityForm->fillFromModel($this->personnelModelData);
            $this->syncArraysFromLaborActivityForm();
        } else {
            $this->hydrateLaborActivityFormFromArrays();
        }
    }

    public function confirmPersonnel(): void
    {
        $this->personnelModelData->update(['is_pending' => false]);
        $this->dispatch('addError', __('Personnel was updated successfully!'));
    }

    public function store()
    {
        $this->authorize('update-personnels', $this->personnelModel);
        $this->syncArraysFromPersonalForm();
        $this->syncArraysFromDocumentForm();
        $this->syncArraysFromEducationForm();
        $this->syncArraysFromLaborActivityForm();
        $currentStep = (int) $this->step;

        if ($currentStep === 1) {
            $this->validate($this->validationRules()[1]);
        } elseif ($currentStep === 2 && $this->shouldValidateStep(2)) {
            $rules = $this->validationRules()[2] ?? [];
            if ($rules) {
                $this->validate($rules);
            }
        } elseif ($currentStep === 3 && $this->shouldValidateStep(3)) {
            $rules = $this->validationRules()[3] ?? [];
            if ($rules) {
                $this->validate($rules);
            }
        } elseif ($currentStep === 4 && $this->shouldValidateStep(4)) {
            $rules = $this->validationRules()[4] ?? [];
            if ($rules) {
                $this->validate($rules);
            }
        }

        if (! empty($this->avatar)) {
            $this->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }
        $personnelData = $this->modifyArray($this->personnel, $this->personnelModelData->dateList());

        if (in_array($this->step, [2, 3, 4], true)) {
            $this->completeStep(actionSave: true);
        }

        $laborActivities = collect($this->laborActivityForm->laborActivityList ?? [])
            ->map(fn ($activity) => Arr::except($activity, ['time']))
            ->all();

        $ranks = $this->laborActivityForm->rankList ?? [];

        DB::transaction(function () use ($personnelData, $laborActivities, $ranks) {
            $this->personnelModelData->update($personnelData);
            $this->handleSingleAssociation(relation: 'document', data: $this->document, model: PersonnelIdentityDocument::class, differentRelationName: 'idDocuments');
            $this->handleAssociations(relation: 'cards', list: $this->service_cards_list, uniqueKeys: 'card_number', model: PersonnelCard::class);
            $this->handleAssociations(relation: 'passports', list: $this->passports_list, uniqueKeys: 'serial_number', model: PersonnelPassports::class);
            $this->handleSingleAssociation(relation: 'education', data: $this->education, model: PersonnelEducation::class);
            $this->handleAssociations(relation: 'extraEducations', list: $this->extra_education_list, uniqueKeys: 'diplom_no', model: PersonnelExtraEducation::class);
            $this->handleAssociations(relation: 'laborActivities', list: $laborActivities, uniqueKeys: 'join_date', model: PersonnelLaborActivity::class);
            $this->handleAssociations(relation: 'ranks', list: $ranks, uniqueKeys: 'given_date', model: PersonnelRank::class, tabelCheck: true);
            $this->handleAssociations(relation: 'military', list: $this->military_list, uniqueKeys: 'start_date', model: PersonnelMilitaryService::class, tabelCheck: true);
            $this->handleAssociations(relation: 'injuries', list: $this->injury_list, uniqueKeys: ['description', 'date_time'], model: PersonnelInjury::class, tabelCheck: true);
            $this->handleAssociations(relation: 'captives', list: $this->captivity_list, uniqueKeys: 'taken_captive_date', model: PersonnelTakenCaptive::class, tabelCheck: true);
            $this->handleAssociations(relation: 'awards', list: $this->award_list, uniqueKeys: ['award_id', 'given_date'], model: PersonnelAward::class, tabelCheck: true);
            $this->handleAssociations(relation: 'punishments', list: $this->punishment_list, uniqueKeys: ['punishment_id', 'given_date'], model: PersonnelPunishment::class, tabelCheck: true);
            $this->handleAssociations(relation: 'kinships', list: $this->kinship_list, uniqueKeys: 'kinship_id', model: PersonnelKinship::class);
            $this->handleAssociations(relation: 'foreignLanguages', list: $this->language_list, uniqueKeys: 'language_id');
            $this->handleAssociations(relation: 'participations', list: $this->event_list, uniqueKeys: 'event_name', model: PersonnelParticipationEvent::class);
            $this->handleAssociations(relation: 'degreeAndNames', list: $this->degree_list, uniqueKeys: 'degree_and_name_id', model: PersonnelScientificDegreeAndName::class);
            $this->handleAssociations(relation: 'elections', list: $this->election_list, uniqueKeys: 'elected_date', model: PersonnelElectedElectoral::class);
            //            $this->handleAssociations(relation: 'criminals', list: $this->criminal_list, uniqueKeys: ['punishment_id', 'given_date'], model: PersonnelCriminal::class,tabelCheck: true);

            if (! empty($this->personnel_extra)) {
                $this->personnelModelData->update($this->personnel_extra);
            }
        });

        $this->dispatch('personnelAdded', __('Personnel was updated successfully!'));
    }
}
