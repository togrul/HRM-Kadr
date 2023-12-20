<?php

namespace App\Livewire\Personnel;

use Livewire\Component;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;
use App\Livewire\Traits\PersonnelCrud;

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
            'criminals',
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
            'degreeAndNames'
        ])
            ->where('id',$this->personnelModel)
            ->first();
    }

    public function store()
    {
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        if(!empty($this->avatar))
        {
            $this->personnel['photo'] = $this->avatar->store('personnel','public');
        }
        $personnelData = $this->modfiyArray($this->personnel);

        ($this->step == 2 || $this->step == 3) && $this->completeStep();

         DB::transaction(function () use($personnelData) {
            $this->personnelModelData->update($personnelData);
            if(in_array('document',$this->completedSteps))
            {
                $documentData = $this->modfiyArray($this->document);
                $this->personnelModelData->idDocuments()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no],$documentData);   
            }
            if(in_array('education',$this->completedSteps))
            {
                $educationData = $this->modfiyArray($this->education);
                $this->personnelModelData->education()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no],$educationData);   
            }
            if(!empty($this->extra_education_list))
            {
                foreach($this->extra_education_list as $ext)
                {
                    $extData = $this->modfiyArray($ext);
                    $extDataList[] = $extData;
                    $this->personnelModelData->extraEducations()->updateOrCreate(['diplom_no' => $ext['diplom_no']],$extData);
                }
                $IdToKeep = collect($extDataList)->pluck('diplom_no');
                $this->personnelModelData->extraEducations()->whereNotIn('diplom_no', $IdToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->extraEducations()->delete();
            }
            if(!empty($this->labor_activities_list))
            {
                foreach($this->labor_activities_list as $lbr)
                {
                    $lbrData = $this->modfiyArray($lbr);
                    $lbrDataList[] =  $lbrData;
                    $this->personnelModelData->laborActivities()->updateOrCreate(['join_date' => $lbr['join_date']],$lbrData);
                }
                $IdToKeep = collect($lbrDataList)->pluck('join_date');
                $this->personnelModelData->laborActivities()->whereNotIn('join_date', $IdToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->laborActivities()->delete();
            }
            if(!empty($this->rank_list))
            {
                foreach($this->rank_list as $rankList)
                {
                    $rnkData = $this->modfiyArray($rankList);
                    $rnkDataList[] = $rnkData;
                    $this->personnelModelData->ranks()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no,'given_date' => $rankList['given_date']],$rnkData);
                }
                $givenDataKeep = collect($rnkDataList)->pluck('given_date');
                $this->personnelModelData->ranks()->whereNotIn('given_date', $givenDataKeep)->delete();
            }
            else
            {
                $this->personnelModelData->ranks()->delete();
            }
            if(!empty($this->military_list))
            {
                foreach($this->military_list as $militaryList)
                {
                    $militaryData = $this->modfiyArray($militaryList);
                    $militaryDataList[] = $militaryData;
                    $this->personnelModelData->military()->updateOrCreate(['tabel_no' => $this->personnelModelData->tabel_no,'start_date' => $militaryList['start_date']],$militaryData);
                }
                $idToKeep = collect($militaryDataList)->pluck('start_date');
                $this->personnelModelData->military()->whereNotIn('start_date', $idToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->military()->delete();
            }

            if(!empty($this->award_list))
            {
                foreach($this->award_list as $awardList)
                {
                    $awardData = $this->modfiyArray($awardList);
                    $awardDataList[] = $awardData;
                    $this->personnelModelData->awards()
                            ->updateOrCreate(
                                [
                                'tabel_no' => $this->personnelModelData->tabel_no,
                                'award_id' => $awardList['award_id']['id'],
                                'given_date' => $awardList['given_date']
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
            }
            else
            {
                $this->personnelModelData->awards()->delete();
            }
            if(!empty($this->punishment_list))
            {
                foreach($this->punishment_list as $punishmentList)
                {
                    $punishmentData = $this->modfiyArray($punishmentList);
                    $punishmentDataList[] = $punishmentData;
                    $this->personnelModelData->punishments()->updateOrCreate(['punishment_id' => $punishmentList['punishment_id'],'given_date' => $punishmentList['given_date']],$punishmentData);
                }
                $punishmentIdKeep = collect($punishmentDataList)->pluck('punishment_id');
                $givenDataKeep = collect($punishmentDataList)->pluck('given_date');
                $this->personnelModelData->punishments()->whereNotIn('punishment_id', $punishmentIdKeep)->whereNotIn('given_date', $givenDataKeep)->delete();
            }
            else
            {
                $this->personnelModelData->punishments()->delete();
            }
            if(!empty($this->criminal_list))
            {
                foreach($this->criminal_list as $criminalList)
                {
                    $criminalData = $this->modfiyArray($criminalList);
                    $criminalDataList[] = $criminalData;
                    $this->personnelModelData->criminals()->updateOrCreate(['punishment_id' => $criminalList['punishment_id'],'given_date' => $criminalList['given_date']],$criminalData);
                }
                $punishmentIdKeep = collect($criminalDataList)->pluck('punishment_id');
                $givenDataKeep = collect($criminalDataList)->pluck('given_date');
                $this->personnelModelData->criminals()->whereNotIn('punishment_id', $punishmentIdKeep)->whereNotIn('given_date', $givenDataKeep)->delete();
            }
            else
            {
                $this->personnelModelData->criminals()->delete();
            }
            if(!empty($this->kinship_list))
            {
                foreach($this->kinship_list as $kinshipList)
                {
                    $kinshipData = $this->modfiyArray($kinshipList);
                    $kinshipDataList[] = $kinshipData;
                    $this->personnelModelData->kinships()->updateOrCreate(['kinship_id' => $kinshipList['kinship_id']],$kinshipData);
                }
                $IdToKeep = collect($kinshipDataList)->pluck('kinship_id');
                $this->personnelModelData->kinships()->whereNotIn('kinship_id', $IdToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->kinships()->delete();
            }
            if(!empty($this->language_list))
            {
                foreach($this->language_list as $languageList)
                {
                    $languageData = $this->modfiyArray($languageList);
                    $languageDataList[] = $languageData;
                    $this->personnelModelData->foreignLanguages()->updateOrCreate(['language_id' => $languageList['language_id']],$languageData);
                }
                $IdToKeep = collect($languageDataList)->pluck('language_id');
                $this->personnelModelData->foreignLanguages()->whereNotIn('language_id', $IdToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->foreignLanguages()->delete();
            }
            if(!empty($this->event_list))
            {
                foreach($this->event_list as $eventList)
                {
                    $eventData = $this->modfiyArray($eventList);
                    $eventDataList[] = $eventData;
                    $this->personnelModelData->participations()->updateOrCreate(['event_name' => $eventList['event_name']],$eventData);
                }
                $IdToKeep = collect($eventDataList)->pluck('event_name');
                $this->personnelModelData->participations()->whereNotIn('event_name', $IdToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->participations()->delete();
            }
            if(!empty($this->degree_list))
            {
                foreach($this->degree_list as $degreeList)
                {
                    $degreeData = $this->modfiyArray($degreeList);
                    $degreeDataList[] = $degreeData;
                    $this->personnelModelData->degreeAndNames()->updateOrCreate(['degree_and_name_id' =>$degreeData['degree_and_name_id']],$degreeData);
                }
                $IdToKeep = collect($degreeDataList)->pluck('degree_and_name_id');
                $this->personnelModelData->degreeAndNames()->whereNotIn('degree_and_name_id', $IdToKeep)->delete();
            }
            else
            {
                $this->personnelModelData->degreeAndNames()->delete();
            }
         });

         $this->dispatch('personnelAdded',__('Personnel was updated successfully!'));
    }
}
