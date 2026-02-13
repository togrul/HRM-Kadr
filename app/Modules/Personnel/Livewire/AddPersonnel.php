<?php

namespace App\Modules\Personnel\Livewire;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Livewire\Forms\Personnel\PersonalInformationForm;
use App\Modules\Personnel\Services\PersonnelFormAssembler;
use App\Modules\Personnel\Support\Traits\PersonnelCrud;
use App\Modules\Personnel\Support\Traits\RelationCruds\RelationCrudTrait;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddPersonnel extends Component
{
    use AuthorizesRequests;
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

        $assembled = app(PersonnelFormAssembler::class)->buildForStore(
            personalForm: $this->personalForm,
            documentForm: $this->documentForm,
            educationForm: $this->educationForm,
            laborActivityForm: $this->laborActivityForm,
            historyForm: $this->historyForm,
            awardsPunishmentsForm: $this->awardsPunishmentsForm,
            kinshipForm: $this->kinshipForm,
            miscForm: $this->miscForm,
            dateFields: $modelInstance->dateList(),
            dateNormalizer: fn (array $payload, array $dates): array => $this->modifyArray($payload, $dates),
            forcePending: ! auth()->user()->can('confirmation-general')
        );

        in_array($this->step, [2, 3, 4], true) && $this->completeStep();

        DB::transaction(function () use ($assembled) {
            $personnel = Personnel::create($assembled['personnel_data']);
            $relationPayloads = $assembled['relation_payloads'];
            $this->createPersonnelRelations($personnel, $relationPayloads);

            if (! empty($assembled['personnel_extra'])) {
                $personnel->update($assembled['personnel_extra']);
            }
        });
        $this->dispatchPersonnelStored(__('Personnel was added successfully!'));
        $this->dispatchModalCloseEvent();
    }

    public function mount()
    {
        $this->authorize('create', Personnel::class);
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
