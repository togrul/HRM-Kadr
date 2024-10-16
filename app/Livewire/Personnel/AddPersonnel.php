<?php

namespace App\Livewire\Personnel;

use App\Livewire\Traits\PersonnelCrud;
use App\Models\Personnel;
use App\Models\PersonnelAward;
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
use App\Models\PersonnelPunishment;
use App\Models\PersonnelRank;
use App\Models\PersonnelScientificDegreeAndName;
use App\Models\PersonnelTakenCaptive;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AddPersonnel extends Component
{
    use PersonnelCrud;

    public function store()
    {
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        $modelInstance = new Personnel;
        $personnelData = $this->modifyArray($this->personnel, $modelInstance->dateList());

        if (! empty($this->avatar)) {
            $this->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }

        ($this->step == 2 || $this->step == 3) && $this->completeStep();

        DB::transaction(function () use ($personnelData) {
            $personnel = Personnel::create($personnelData);
            if (in_array('document', $this->completedSteps)) {
                $documentInstance = new PersonnelIdentityDocument;
                $documentData = $this->modifyArray($this->document, $documentInstance->dateList());
                $personnel->idDocuments()->create($documentData);
            }
            if (in_array('education', $this->completedSteps)) {
                $educationInstance = new PersonnelEducation;
                $educationData = $this->modifyArray($this->education, $educationInstance->dateList());
                $personnel->education()->create($educationData);
            }
            if (! empty($this->extra_education_list)) {
                foreach ($this->extra_education_list as $ext) {
                    $extraEducationInstance = new PersonnelExtraEducation;
                    $extData = $this->modifyArray($ext, $extraEducationInstance->dateList());
                    $personnel->extraEducations()->create($extData);
                }
            }
            if (! empty($this->labor_activities_list)) {
                foreach ($this->labor_activities_list as $lbr) {
                    $laborActivityInstance = new PersonnelLaborActivity;
                    $lbrData = $this->modifyArray($lbr, $laborActivityInstance->dateList());
                    $personnel->laborActivities()->create($lbrData);
                }
            }
            if (! empty($this->rank_list)) {
                foreach ($this->rank_list as $rankList) {
                    $rankInstance = new PersonnelRank;
                    $rnkData = $this->modifyArray($rankList, $rankInstance->dateList());
                    $personnel->ranks()->create($rnkData);
                }
            }
            if (! empty($this->military_list)) {
                foreach ($this->military_list as $militaryList) {
                    $militaryInstance = new PersonnelMilitaryService;
                    $militaryData = $this->modifyArray($militaryList, $militaryInstance->dateList());
                    $personnel->military()->create($militaryData);
                }
            }
            if (! empty($this->injury_list)) {
                foreach ($this->injury_list as $injuryList) {
                    $injuryInstance = new PersonnelInjury;
                    $injuryData = $this->modifyArray($injuryList, $injuryInstance->dateList());
                    $personnel->injuries()->create($injuryData);
                }
            }
            if (! empty($this->captivity_list)) {
                foreach ($this->captivity_list as $captivityList) {
                    $captivityInstance = new PersonnelTakenCaptive;
                    $captivityData = $this->modifyArray($captivityList, $captivityInstance->dateList());
                    $personnel->captives()->create($captivityData);
                }
            }
            if (! empty($this->award_list)) {
                foreach ($this->award_list as $awardList) {
                    $awardInstance = new PersonnelAward;
                    $awardData = $this->modifyArray($awardList, $awardInstance->dateList());
                    $personnel->awards()->create($awardData);
                }
            }
            if (! empty($this->punishment_list)) {
                foreach ($this->punishment_list as $punishmentList) {
                    $punishmentInstance = new PersonnelPunishment;
                    $punishmentData = $this->modifyArray($punishmentList, $punishmentInstance->dateList());
                    $personnel->punishments()->create($punishmentData);
                }
            }
            //            if(!empty($this->criminal_list))
            //            {
            //                foreach($this->criminal_list as $criminalList)
            //                {
            //                    $criminalInstance = new PersonnelCriminal();
            //                    $criminalData = $this->modifyArray($criminalList,$criminalInstance->dateList());
            //                    $personnel->criminals()->create($criminalData);
            //                }
            //            }
            if (! empty($this->kinship_list)) {
                foreach ($this->kinship_list as $kinshipList) {
                    $kinshipInstance = new PersonnelKinship;
                    $kinshipData = $this->modifyArray($kinshipList, $kinshipInstance->dateList());
                    $personnel->kinships()->create($kinshipData);
                }
            }
            if (! empty($this->language_list)) {
                foreach ($this->language_list as $languageList) {
                    $languageData = $this->modifyArray($languageList);
                    $personnel->foreignLanguages()->create($languageData);
                }
            }
            if (! empty($this->event_list)) {
                foreach ($this->event_list as $eventList) {
                    $eventInstance = new PersonnelParticipationEvent;
                    $eventData = $this->modifyArray($eventList, $eventInstance->dateList());
                    $personnel->participations()->create($eventData);
                }
            }
            if (! empty($this->degree_list)) {
                foreach ($this->degree_list as $degreeList) {
                    $degreeInstance = new PersonnelScientificDegreeAndName;
                    $degreeData = $this->modifyArray($degreeList, $degreeInstance->dateList());
                    $personnel->degreeAndNames()->create($degreeData);
                }
            }
            if (! empty($this->election_list)) {
                foreach ($this->election_list as $electionList) {
                    $electionInstance = new PersonnelElectedElectoral;
                    $electionData = $this->modifyArray($electionList, $electionInstance->dateList());
                    $personnel->elections()->create($electionData);
                }
            }
            if (! empty($this->personnel_extra)) {
                $personnel->update($this->personnel_extra);
            }
        });
        $this->dispatch('personnelAdded', __('Personnel was added successfully!'));
    }

    public function mount()
    {
        $this->title = __('New personnel');
        $this->step = 1;
    }
}
