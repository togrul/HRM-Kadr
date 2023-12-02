<?php

namespace App\Livewire\Personnel;

use Livewire\Component;
use App\Models\Personnel;
use Livewire\Attributes\Rule;
use App\Livewire\Traits\PersonnelCrud;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AddPersonnel extends Component
{
    use PersonnelCrud;
     
    public function store()
    {
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        $personnelData = $this->modfiyArray($this->personnel);

        if(!empty($this->avatar))
            $this->personnel['photo'] = $this->avatar->store('personnel','public');

        ($this->step == 2 || $this->step == 3) && $this->completeStep();

        DB::transaction(function () use($personnelData) {
            $personnel = Personnel::create($personnelData);
            if(in_array('document',$this->completedSteps))
            {
                $documentData = $this->modfiyArray($this->document);
                $personnel->idDocuments()->create($documentData);   
            }
            if(in_array('education',$this->completedSteps))
            {
                $educationData = $this->modfiyArray($this->education);
                $personnel->education()->create($educationData);   
            }
            if(!empty($this->extra_education_list))
            {
                foreach($this->extra_education_list as $ext)
                {
                    $extData = $this->modfiyArray($ext);
                    $personnel->extraEducations()->create($extData);
                }
            }
            if(!empty($this->labor_activities_list))
            {
                foreach($this->labor_activities_list as $lbr)
                {
                    $lbrData = $this->modfiyArray($lbr);
                    $personnel->laborActivities()->create($lbrData);
                }
            }
            if(!empty($this->rank_list))
            {
                foreach($this->rank_list as $rankList)
                {
                    $rnkData = $this->modfiyArray($rankList);
                    $personnel->ranks()->create($rnkData);
                }
            }
            if(!empty($this->military_list))
            {
                foreach($this->military_list as $militaryList)
                {
                    $militaryData = $this->modfiyArray($militaryList);
                    $personnel->military()->create($militaryData);
                }
            }
            if(!empty($this->award_list))
            {
                foreach($this->award_list as $awardList)
                {
                    $awardData = $this->modfiyArray($awardList);
                    $personnel->awards()->create($awardData);
                }
            }
            if(!empty($this->punishment_list))
            {
                foreach($this->punishment_list as $punishmentList)
                {
                    $punishmentData = $this->modfiyArray($punishmentList);
                    $personnel->punishments()->create($punishmentData);
                }
            }
            if(!empty($this->criminal_list))
            {
                foreach($this->criminal_list as $criminalList)
                {
                    $criminalData = $this->modfiyArray($criminalList);
                    $personnel->criminals()->create($criminalData);
                }
            }
            if(!empty($this->kinship_list))
            {
                foreach($this->kinship_list as $kinshipList)
                {
                    $kinshipData = $this->modfiyArray($kinshipList);
                    $personnel->kinships()->create($kinshipData);
                }
            }
            if(!empty($this->language_list))
            {
                foreach($this->language_list as $languageList)
                {
                    $languageData = $this->modfiyArray($languageList);
                    $personnel->foreignLanguages()->create($languageData);
                }
            }
            if(!empty($this->event_list))
            {
                foreach($this->event_list as $eventList)
                {
                    $eventData = $this->modfiyArray($eventList);
                    $personnel->participations()->create($eventData);
                }
            }
            if(!empty($this->degree_list))
            {
                foreach($this->degree_list as $degreeList)
                {
                    $degreeData = $this->modfiyArray($degreeList);
                    $personnel->degreeAndNames()->create($degreeData);
                }
            }
        });
        $this->dispatch('personnelAdded',__('Personnel was added successfully!'));
    }
    
    public function mount()
    {
        $this->title = __('New personnel');
        $this->step = 1;
    }
}
