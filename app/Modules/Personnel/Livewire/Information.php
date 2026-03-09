<?php

namespace App\Modules\Personnel\Livewire;

use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Modules\Personnel\Support\Traits\DispatchesPersonnelUiEvents;
use App\Modules\Personnel\Support\Traits\Information\ContractTrait;
use App\Modules\Personnel\Support\Traits\Information\DisposalTrait;
use App\Modules\Personnel\Support\Traits\Information\EducationRequestTrait;
use App\Modules\Personnel\Support\Traits\Information\MasterDegreeTrait;
use App\Modules\Personnel\Support\Traits\Information\PensionCardTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Traits\NormalizesDropdownPayloads;
use App\Models\Personnel;
use App\Models\Rank;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[On('contractAdded')]
class Information extends Component
{
    use AuthorizesRequests;
    use DispatchesPersonnelUiEvents;
    use ContractTrait;
    use DisposalTrait;
    use EducationRequestTrait;
    use MasterDegreeTrait;
    use FillComplexArrayTrait;
    use PensionCardTrait;
    use DropdownConstructTrait;
    use NormalizesDropdownPayloads;

    public string $title;

    #[Locked]
    public string $personnelModel;

    public $personnelModelData;

    public array $steps = [];

    public array $stepViews = [];

    public int $currentStep = 0;

    public function validationRules(): array
    {
        return [
            'contract' => $this->getContractRules(),
            'educationRequest' => $this->getEducationRequestsRules(),
            'masterDegree' => $this->getMasterDegreesRules(),
            'pensionCard' => $this->getPensionCardsRules(),
            'disposal' => $this->getDisposalRules(),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'contracts.rank_id' => __('personnel::information.fields.rank'),
            'contracts.contract_date' => __('personnel::information.fields.contract_date'),
            'contracts.contract_refresh_date' => __('personnel::information.fields.contract_refresh_date'),
            'contracts.contract_duration' => __('personnel::information.fields.contract_duration'),
            'contracts.contract_ends_at' => __('personnel::information.fields.contract_end_date'),
            'education.education_place' => __('personnel::information.fields.education_place'),
            'education.request_date' => __('personnel::information.fields.request_date'),
            'education.specialty' => __('personnel::information.fields.profession'),
            'masterDegrees.degree' => __('personnel::information.fields.degree'),
            'masterDegrees.given_date' => __('personnel::information.fields.given_date'),
            'masterDegrees.approved_date' => __('personnel::information.fields.approved_date'),
            'pensionCards.card_no' => __('personnel::information.fields.card_number'),
            'pensionCards.given_date' => __('personnel::information.fields.given_date'),
            'pensionCards.expiry_date' => __('personnel::information.fields.expiry_date'),
            'disposals.disposal_date' => __('personnel::information.fields.disposal_date'),
        ];
    }

    public function setCurrentStep($key): void
    {
        if (array_key_exists($key, $this->steps)) {
            $this->currentStep = $key;
        }

        $this->resetSelected();
        $this->resetValidation();
    }

    #[Computed]
    public function contractRankOptions(): array
    {
        $localeColumn = 'name_'.config('app.locale');

        $base = Rank::query()
            ->select('id', DB::raw("{$localeColumn} as label"))
            ->orderBy($localeColumn);

        return $this->optionsWithSelected(
            base: $base,
            searchCol: null,
            searchTerm: null,
            selectedId: data_get($this->contracts, 'rank_id'),
            limit: 80
        );
    }

    public function resetSelected(): void
    {
        $this->reset('contracts', 'education', 'selectedRequest', 'masterDegrees', 'selectedDegree', 'selectedDisposal', 'disposals');
    }

    protected function formatDate($date): string
    {
        return $date instanceof \DateTime ? $date->format('d.m.Y') : (string) $date;
    }

    public function mount()
    {
        $this->personnelModelData = Personnel::with([
            'contracts.rank',
            'educationRequests',
            'masterDegrees',
            'pensionCards',
            'disposals',
        ])
            ->withTrashed()
            ->where('tabel_no', $this->personnelModel)
            ->firstOrFail();

        $this->authorize('update', $this->personnelModelData);

        $this->title = __('personnel::common.titles.edit_personnel');
        $this->steps = [
            __('personnel::information.tabs.contracts'),
            __('personnel::information.tabs.education_requests'),
            __('personnel::information.tabs.master_degrees'),
            __('personnel::information.tabs.pension_cards'),
            __('personnel::information.tabs.disposals'),
        ];
        $this->stepViews = [
            'contracts',
            'education-requests',
            'master-degrees',
            'pension-cards',
            'disposals',
        ];

        $this->currentStep = 0;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.information');
    }
}
