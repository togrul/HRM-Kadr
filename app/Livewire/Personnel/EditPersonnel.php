<?php

namespace App\Livewire\Personnel;

use App\Livewire\Traits\PersonnelCrud;
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
use App\Models\PersonnelPunishment;
use App\Models\PersonnelRank;
use App\Models\PersonnelScientificDegreeAndName;
use App\Models\PersonnelTakenCaptive;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditPersonnel extends Component
{
    use PersonnelCrud;

    public $updatePersonnel;

    public $personnelModelData;

    public $personnelModel;

    public function mount()
    {
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
            ->where('id', $this->personnelModel)
            ->first();
    }

    public function store()
    {
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        if (! empty($this->avatar)) {
            $this->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }
        $personnelData = $this->modifyArray($this->personnel, $this->personnelModelData->dateList());

        ($this->step == 2 || $this->step == 3) && $this->completeStep();

        DB::transaction(function () use ($personnelData) {
            $this->personnelModelData->update($personnelData);
            if (in_array('document', $this->completedSteps)) {
                $documentInstance = new PersonnelIdentityDocument;
                $documentData = $this->modifyArray($this->document, $documentInstance->dateList());
                $this->personnelModelData->idDocuments()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no], $documentData);
            }
            if (! empty($this->service_cards_list)) {
                foreach ($this->service_cards_list as $card) {
                    $serviceCardInstance = new PersonnelCard;
                    $extData = $this->modifyArray($card, $serviceCardInstance->dateList());
                    $extDataList[] = $extData;
                    $this->personnelModelData->cards()->updateOrCreate(['card_number' => $card['card_number']], $extData);
                }
                $IdToKeep = collect($extDataList)->pluck('card_number');
                $this->personnelModelData->cards()->whereNotIn('card_number', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->cards()->delete();
            }
            if (in_array('education', $this->completedSteps)) {
                $educationInstance = new PersonnelEducation;
                $educationData = $this->modifyArray($this->education, $educationInstance->dateList());
                $this->personnelModelData->education()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no], $educationData);
            }
            if (! empty($this->extra_education_list)) {
                foreach ($this->extra_education_list as $ext) {
                    $extraEducationInstance = new PersonnelExtraEducation;
                    $extData = $this->modifyArray($ext, $extraEducationInstance->dateList());
                    $extDataList[] = $extData;
                    $this->personnelModelData->extraEducations()->updateOrCreate(['diplom_no' => $ext['diplom_no']], $extData);
                }
                $IdToKeep = collect($extDataList)->pluck('diplom_no');
                $this->personnelModelData->extraEducations()->whereNotIn('diplom_no', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->extraEducations()->delete();
            }
            if (! empty($this->labor_activities_list)) {
                foreach ($this->labor_activities_list as $lbr) {
                    $laborActivityInstance = new PersonnelLaborActivity;
                    $lbrData = $this->modifyArray($lbr, $laborActivityInstance->dateList());
                    $lbrDataList[] = $lbrData;
                    $this->personnelModelData->laborActivities()->updateOrCreate(['join_date' => $lbrData['join_date']], $lbrData);
                }
                $IdToKeep = collect($lbrDataList)->pluck('join_date');
                $this->personnelModelData->laborActivities()->whereNotIn('join_date', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->laborActivities()->delete();
            }
            if (! empty($this->rank_list)) {
                foreach ($this->rank_list as $rankList) {
                    $rankInstance = new PersonnelRank;
                    $rnkData = $this->modifyArray($rankList, $rankInstance->dateList());
                    $rnkDataList[] = $rnkData;
                    $this->personnelModelData->ranks()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no, 'given_date' => $rnkData['given_date']], $rnkData);
                }
                $givenDataKeep = collect($rnkDataList)->pluck('given_date');
                $this->personnelModelData->ranks()->whereNotIn('given_date', $givenDataKeep)->delete();
            } else {
                $this->personnelModelData->ranks()->delete();
            }
            if (! empty($this->military_list)) {
                foreach ($this->military_list as $militaryList) {
                    $militaryInstance = new PersonnelMilitaryService;
                    $militaryData = $this->modifyArray($militaryList, $militaryInstance->dateList());
                    $militaryDataList[] = $militaryData;
                    $this->personnelModelData->military()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no, 'start_date' => $militaryData['start_date']], $militaryData);
                }
                $idToKeep = collect($militaryDataList)->pluck('start_date');
                $this->personnelModelData->military()->whereNotIn('start_date', $idToKeep)->delete();
            } else {
                $this->personnelModelData->military()->delete();
            }

            if (! empty($this->injury_list)) {
                foreach ($this->injury_list as $injuryList) {
                    $injuryInstance = new PersonnelInjury;
                    $injuryData = $this->modifyArray($injuryList, $injuryInstance->dateList());
                    $injuryDataList[] = $injuryData;
                    $this->personnelModelData->injuries()->updateOrCreate([
                        'tabel_no' => $this->personnelModelData->tabel_no,
                        'injury_type' => $injuryData['injury_type'],
                        'date_time' => $injuryData['date_time'],
                    ], $injuryData);
                }
                $idToKeep = collect($injuryDataList)->pluck('injury_type');
                $dateToKeep = collect($injuryDataList)->pluck('date_time');
                $this->personnelModelData->injuries()
                    ->whereNotIn('injury_type', $idToKeep)
                    ->whereNotIn('date_time', $dateToKeep)
                    ->delete();
            } else {
                $this->personnelModelData->injuries()->delete();
            }

            if (! empty($this->captivity_list)) {
                foreach ($this->captivity_list as $captivityList) {
                    $captivityInstance = new PersonnelTakenCaptive;
                    $captivityData = $this->modifyArray($captivityList, $captivityInstance->dateList());
                    $captivityDataList[] = $captivityData;
                    $this->personnelModelData->captives()->updateOrCreate([
                        'tabel_no' => $this->personnelModelData->tabel_no,
                        'taken_captive_date' => $captivityData['taken_captive_date'],
                    ], $captivityData);
                }
                $idToKeep = collect($captivityDataList)->pluck('taken_captive_date');
                $this->personnelModelData->captives()->whereNotIn('taken_captive_date', $idToKeep)->delete();
            } else {
                $this->personnelModelData->captives()->delete();
            }

            if (! empty($this->award_list)) {
                foreach ($this->award_list as $awardList) {
                    $awardInstance = new PersonnelAward;
                    $awardData = $this->modifyArray($awardList, $awardInstance->dateList());
                    $awardDataList[] = $awardData;
                    $this->personnelModelData->awards()
                        ->updateOrCreate(
                            [
                                'tabel_no' => $this->personnelModelData->tabel_no,
                                'award_id' => $awardList['award_id']['id'],
                                'given_date' => $awardData['given_date'],
                            ],
                            $awardData
                        );
                }
                $awardIdKeep = collect($awardDataList)->pluck('award_id');
                $givenDataKeep = collect($awardDataList)->pluck('given_date');
                $this->personnelModelData->awards()
                    ->whereNotIn('award_id', $awardIdKeep)
                    ->whereNotIn('given_date', $givenDataKeep)
                    ->delete();
            } else {
                $this->personnelModelData->awards()->delete();
            }
            if (! empty($this->punishment_list)) {
                foreach ($this->punishment_list as $punishmentList) {
                    $punishmentInstance = new PersonnelPunishment;
                    $punishmentData = $this->modifyArray($punishmentList, $punishmentInstance->dateList());
                    $punishmentDataList[] = $punishmentData;
                    $this->personnelModelData->punishments()->updateOrCreate(['punishment_id' => $punishmentList['punishment_id'], 'given_date' => $punishmentData['given_date']], $punishmentData);
                }
                $punishmentIdKeep = collect($punishmentDataList)->pluck('punishment_id');
                $givenDataKeep = collect($punishmentDataList)->pluck('given_date');
                $this->personnelModelData->punishments()->whereNotIn('punishment_id', $punishmentIdKeep)->whereNotIn('given_date', $givenDataKeep)->delete();
            } else {
                $this->personnelModelData->punishments()->delete();
            }
            //            if(!empty($this->criminal_list))
            //            {
            //                foreach($this->criminal_list as $criminalList)
            //                {
            //                    $criminalInstance = new PersonnelCriminal();
            //                    $criminalData = $this->modifyArray($criminalList,$criminalInstance->dateList());
            //                    $criminalDataList[] = $criminalData;
            //                    $this->personnelModelData->criminals()->updateOrCreate(['punishment_id' => $criminalData['punishment_id'],'given_date' => $criminalData['given_date']],$criminalData);
            //                }
            //                $punishmentIdKeep = collect($criminalDataList)->pluck('punishment_id');
            //                $givenDataKeep = collect($criminalDataList)->pluck('given_date');
            //                $this->personnelModelData->criminals()->whereNotIn('punishment_id', $punishmentIdKeep)->whereNotIn('given_date', $givenDataKeep)->delete();
            //            }
            //            else
            //            {
            //                $this->personnelModelData->criminals()->delete();
            //            }
            if (! empty($this->kinship_list)) {
                foreach ($this->kinship_list as $kinshipList) {
                    $kinshipInstance = new PersonnelKinship;
                    $kinshipData = $this->modifyArray($kinshipList, $kinshipInstance->dateList());
                    $kinshipDataList[] = $kinshipData;
                    $this->personnelModelData->kinships()->updateOrCreate(['kinship_id' => $kinshipList['kinship_id']], $kinshipData);
                }
                $IdToKeep = collect($kinshipDataList)->pluck('kinship_id');
                $this->personnelModelData->kinships()->whereNotIn('kinship_id', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->kinships()->delete();
            }
            if (! empty($this->language_list)) {
                foreach ($this->language_list as $languageList) {
                    $languageData = $this->modifyArray($languageList);
                    $languageDataList[] = $languageData;
                    $this->personnelModelData->foreignLanguages()->updateOrCreate(['language_id' => $languageList['language_id']], $languageData);
                }
                $IdToKeep = collect($languageDataList)->pluck('language_id');
                $this->personnelModelData->foreignLanguages()->whereNotIn('language_id', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->foreignLanguages()->delete();
            }
            if (! empty($this->event_list)) {
                foreach ($this->event_list as $eventList) {
                    $eventInstance = new PersonnelParticipationEvent;
                    $eventData = $this->modifyArray($eventList, $eventInstance->dateList());
                    $eventDataList[] = $eventData;
                    $this->personnelModelData->participations()->updateOrCreate(['event_name' => $eventList['event_name']], $eventData);
                }
                $IdToKeep = collect($eventDataList)->pluck('event_name');
                $this->personnelModelData->participations()->whereNotIn('event_name', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->participations()->delete();
            }
            if (! empty($this->degree_list)) {
                foreach ($this->degree_list as $degreeList) {
                    $degreeInstance = new PersonnelScientificDegreeAndName;
                    $degreeData = $this->modifyArray($degreeList, $degreeInstance->dateList());
                    $degreeDataList[] = $degreeData;
                    $this->personnelModelData->degreeAndNames()->updateOrCreate(['degree_and_name_id' => $degreeData['degree_and_name_id']], $degreeData);
                }
                $IdToKeep = collect($degreeDataList)->pluck('degree_and_name_id');
                $this->personnelModelData->degreeAndNames()->whereNotIn('degree_and_name_id', $IdToKeep)->delete();
            } else {
                $this->personnelModelData->degreeAndNames()->delete();
            }
            if (! empty($this->election_list)) {
                foreach ($this->election_list as $electionList) {
                    $electionInstance = new PersonnelElectedElectoral;
                    $electedData = $this->modifyArray($electionList, $electionInstance->dateList());
                    $electedDataList[] = $electedData;
                    $this->personnelModelData->elections()->updateOrCreate(['elected_date' => $electedData['elected_date']], $electedData);
                    $IdToKeep = collect($electedDataList)->pluck('elected_date');
                    $this->personnelModelData->elections()->whereNotIn('elected_date', $IdToKeep)->delete();
                }

            } else {
                $this->personnelModelData->elections()->delete();
            }

            if (! empty($this->personnel_extra)) {
                $this->personnelModelData->update($this->personnel_extra);
            }
        });

        $this->dispatch('personnelAdded', __('Personnel was updated successfully!'));
    }
}
