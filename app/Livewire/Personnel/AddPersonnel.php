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

class AddPersonnel extends Component
{
    use PersonnelCrud;
    use RelationCrudTrait;

    public PersonalInformationForm $personalForm;
    public DocumentForm $documentForm;
    public EducationForm $educationForm;
    public LaborActivityForm $laborActivityForm;

    public function store()
    {
        $this->syncArraysFromPersonalForm();
        $this->syncArraysFromDocumentForm();
        $this->syncArraysFromEducationForm();
        $this->syncArraysFromLaborActivityForm();
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        $modelInstance = new Personnel;
        $personnelData = $this->modifyArray($this->personnel, $modelInstance->dateList());
        $personnelData['is_pending'] = ! auth()->user()->can('confirmation-general');

        if (! empty($this->avatar)) {
            $this->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }

        in_array($this->step, [2, 3, 4], true) && $this->completeStep();

        $laborActivities = collect($this->laborActivityForm->laborActivityList ?? [])
            ->map(fn ($activity) => Arr::except($activity, ['time']))
            ->all();

        $ranks = $this->laborActivityForm->rankList ?? [];

        DB::transaction(function () use ($personnelData, $laborActivities, $ranks) {
            $personnel = Personnel::create($personnelData);
            // relation other crud
            $this->createRelatedData($personnel, 'idDocuments', PersonnelIdentityDocument::class, $this->document);
            $this->createMultipleRelatedData($personnel, 'cards', PersonnelCard::class, $this->service_cards_list);
            $this->createMultipleRelatedData($personnel, 'passports', PersonnelPassports::class, $this->passports_list);
            $this->createRelatedData($personnel, 'education', PersonnelEducation::class, $this->education);
            $this->createMultipleRelatedData($personnel, 'extraEducations', PersonnelExtraEducation::class, $this->extra_education_list);
            $this->createMultipleRelatedData($personnel, 'laborActivities', PersonnelLaborActivity::class, $laborActivities);
            $this->createMultipleRelatedData($personnel, 'ranks', PersonnelRank::class, $ranks);
            $this->createMultipleRelatedData($personnel, 'military', PersonnelMilitaryService::class, $this->military_list);
            $this->createMultipleRelatedData($personnel, 'injuries', PersonnelInjury::class, $this->injury_list);
            $this->createMultipleRelatedData($personnel, 'captives', PersonnelTakenCaptive::class, $this->captivity_list);
            $this->createMultipleRelatedData($personnel, 'awards', PersonnelAward::class, $this->award_list);
            $this->createMultipleRelatedData($personnel, 'punishments', PersonnelPunishment::class, $this->punishment_list);
            $this->createMultipleRelatedData($personnel, 'kinships', PersonnelKinship::class, $this->kinship_list);
            $this->createMultipleRelatedData($personnel, 'foreignLanguages', null, $this->language_list);
            $this->createMultipleRelatedData($personnel, 'participations', PersonnelParticipationEvent::class, $this->event_list);
            $this->createMultipleRelatedData($personnel, 'degreeAndNames', PersonnelScientificDegreeAndName::class, $this->degree_list);
            $this->createMultipleRelatedData($personnel, 'elections', PersonnelElectedElectoral::class, $this->election_list);
//            $this->createMultipleRelatedData($personnel, 'criminals', PersonnelCriminal::class, $this->criminal_list);

            if (! empty($this->personnel_extra)) {
                $personnel->update($this->personnel_extra);
            }
        });
        $this->dispatch('personnelAdded', __('Personnel was added successfully!'));
    }

    public function mount()
    {
        $this->authorize('add-personnels');
        $this->title = __('New personnel');
        $this->step = 1;

        if (isset($this->personalForm)) {
            $this->personalForm->resetForm();
            $this->hydratePersonalFormFromArrays();
        }

        if (isset($this->documentForm)) {
            $this->documentForm->resetForm();
            $this->hydrateDocumentFormFromArrays();
        }

        if (isset($this->educationForm)) {
            $this->educationForm->resetForm();
            $this->hydrateEducationFormFromArrays();
        }

        if (isset($this->laborActivityForm)) {
            $this->laborActivityForm->resetForm();
            $this->hydrateLaborActivityFormFromArrays();
        }
    }
}
