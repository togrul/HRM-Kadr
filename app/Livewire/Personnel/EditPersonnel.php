<?php

namespace App\Livewire\Personnel;

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
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditPersonnel extends Component
{
    use PersonnelCrud;
    use RelationCrudTrait;

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
    }

    public function store()
    {
        $this->authorize('update-personnels', $this->personnelModel);
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        if (! empty($this->avatar)) {
            $this->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }
        $personnelData = $this->modifyArray($this->personnel, $this->personnelModelData->dateList());

        if ($this->step == 2 || $this->step == 3) {
            $this->completeStep();
        }

        DB::transaction(function () use ($personnelData) {
            $this->personnelModelData->update($personnelData);
            $this->handleSingleAssociation(relation: 'document', data: $this->document, model: PersonnelIdentityDocument::class, differentRelationName: 'idDocuments');
            $this->handleAssociations(relation: 'cards', list: $this->service_cards_list, uniqueKeys: 'card_number', model: PersonnelCard::class);
            $this->handleAssociations(relation: 'passports', list: $this->passports_list, uniqueKeys: 'serial_number', model: PersonnelPassports::class);
            $this->handleSingleAssociation(relation: 'education', data: $this->education, model: PersonnelEducation::class);
            $this->handleAssociations(relation: 'extraEducations', list: $this->extra_education_list, uniqueKeys: 'diplom_no', model: PersonnelExtraEducation::class);
            $this->handleAssociations(relation: 'laborActivities', list: $this->labor_activities_list, uniqueKeys: 'join_date', model: PersonnelLaborActivity::class);
            $this->handleAssociations(relation: 'ranks', list: $this->rank_list, uniqueKeys: 'given_date', model: PersonnelRank::class, tabelCheck: true);
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
