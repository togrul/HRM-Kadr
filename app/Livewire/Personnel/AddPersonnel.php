<?php

namespace App\Livewire\Personnel;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Livewire\Forms\Personnel\PersonalInformationForm;
use App\Livewire\Traits\PersonnelCrud;
use App\Livewire\Traits\RelationCruds\RelationCrudTrait;
use App\Models\Personnel;
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
    public ServiceHistoryForm $historyForm;
    public AwardsPunishmentsForm $awardsPunishmentsForm;
    public KinshipForm $kinshipForm;
    public MiscellaneousForm $miscForm;

    public function store()
    {
        $this->step == 1 && $this->validate($this->validationRules()[$this->step]);

        $modelInstance = new Personnel;

        if (! empty($this->avatar)) {
            $this->personalForm->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }

        $personalPayload = $this->personalForm->toPayload();
        $personnelData = $this->modifyArray(
            $personalPayload['personnel'] ?? [],
            $modelInstance->dateList()
        );
        $personnelData['is_pending'] = ! auth()->user()->can('confirmation-general');
        $personnelExtra = $personalPayload['personnel_extra'] ?? [];

        $documentPayload = $this->documentPayload();
        $documentData = data_get($documentPayload, 'document', []);
        $serviceCardsList = data_get($documentPayload, 'service_cards.list', []);
        $passportsList = data_get($documentPayload, 'passports.list', []);
        $education = $this->educationForm->educationForPersistence();
        $extraEducations = $this->educationForm->extraEducationsForPersistence();

        in_array($this->step, [2, 3, 4], true) && $this->completeStep();

        $laborActivities = $this->laborActivityForm->laborActivitiesForPersistence();
        $ranks = $this->laborActivityForm->ranksForPersistence();

        $militaryServices = $this->historyForm->militaryForPersistence();
        $injuries = $this->historyForm->injuriesForPersistence();
        $captivities = $this->historyForm->captivitiesForPersistence();
        $awards = $this->awardsPunishmentsForm->awardsForPersistence();
        $punishments = $this->awardsPunishmentsForm->punishmentsForPersistence();
        $kinships = $this->kinshipForm->kinshipsForPersistence();
        $languages = $this->miscForm->languagesForPersistence();
        $events = $this->miscForm->eventsForPersistence();
        $degrees = $this->miscForm->degreesForPersistence();
        $elections = $this->miscForm->electionsForPersistence();

        $relationPayloads = [
            'document' => $documentData,
            'service_cards' => $serviceCardsList,
            'passports' => $passportsList,
            'education' => $education,
            'extra_educations' => $extraEducations,
            'labor_activities' => $laborActivities,
            'ranks' => $ranks,
            'military' => $militaryServices,
            'injuries' => $injuries,
            'captivities' => $captivities,
            'awards' => $awards,
            'punishments' => $punishments,
            'kinships' => $kinships,
            'languages' => $languages,
            'events' => $events,
            'degrees' => $degrees,
            'elections' => $elections,
        ];

        DB::transaction(function () use ($personnelData, $relationPayloads, $personnelExtra) {
            $personnel = Personnel::create($personnelData);
            $this->createPersonnelRelations($personnel, $relationPayloads);

            if (! empty($personnelExtra)) {
                $personnel->update($personnelExtra);
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
        }

        if (isset($this->documentForm)) {
            $this->documentForm->resetForm();
        }

        if (isset($this->educationForm)) {
            $this->educationForm->resetForm();
        }

        if (isset($this->laborActivityForm)) {
            $this->laborActivityForm->resetForm();
        }

        if (isset($this->historyForm)) {
            $this->historyForm->resetForm();
        }

        if (isset($this->awardsPunishmentsForm)) {
            $this->awardsPunishmentsForm->resetForm();
        }

        if (isset($this->kinshipForm)) {
            $this->kinshipForm->resetForm();
        }

        if (isset($this->miscForm)) {
            $this->miscForm->resetForm();
        }
    }
}
