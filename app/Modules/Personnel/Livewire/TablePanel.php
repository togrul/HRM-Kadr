<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use App\Modules\Personnel\Services\PersonnelListStateNormalizer;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Personnel\Services\PersonnelRowActionService;
use App\Modules\Personnel\Services\PersonnelRowViewModelService;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Livewire\WithPagination;

#[Isolate]
class TablePanel extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $status = 'current';

    public array $filters = [];

    public array $structure = [];

    public ?int $selectedPosition = null;

    protected ?array $accessibleStructureCache = null;

    protected ?array $rowActionCapabilities = null;

    public function mount(string $status = 'current', array $filters = [], array $structure = [], ?int $selectedPosition = null): void
    {
        $this->authorize('viewAny', Personnel::class);
        $normalizer = app(PersonnelListStateNormalizer::class);

        $this->status = $normalizer->normalizeStatus($status, ['current', 'leaves', 'all', 'deleted', 'pending']);
        $this->filters = $normalizer->normalizeFilters($filters);
        $this->structure = $normalizer->normalizeStructure($structure);
        $this->selectedPosition = $normalizer->normalizePosition($selectedPosition);
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.table-panel');
    }

    public function handleRowAction(string $type, mixed $payload = null): void
    {
        $this->dispatch('personnelTableRowAction', type: $type, payload: $payload);
    }

    public function rowActions(Personnel $personnel): array
    {
        return app(PersonnelRowActionService::class)->build(
            personnel: $personnel,
            status: $this->status,
            capabilities: $this->rowActionCapabilities()
        );
    }

    public function getTableHeaders(): array
    {
        return [
            __('personnel::common.labels.number'),
            __('personnel::common.labels.tabel'),
            __('personnel::common.labels.fullname'),
            __('personnel::common.labels.structure'),
            __('personnel::common.labels.date'),
            __('services::common.labels.action'),
        ];
    }

    public function getPersonnelsProperty(): LengthAwarePaginator
    {
        return app(PersonnelRowViewModelService::class)->decoratePaginator(
            $this->personnelQuery()->paginate(10, pageName: 'personnelPage')->withQueryString()
        );
    }

    protected function personnelQuery(): Builder
    {
        return app(PersonnelQueryService::class)->build(
            status: $this->status,
            filters: $this->filters,
            selectedStructureIds: $this->structure,
            accessibleStructureIds: $this->accessibleStructureIds(),
            selectedPosition: $this->selectedPosition,
            withStructureTree: true
        );
    }

    protected function accessibleStructureIds(): array
    {
        if (! is_null($this->accessibleStructureCache)) {
            return $this->accessibleStructureCache;
        }

        return $this->accessibleStructureCache = resolve(StructureService::class)->getAccessibleStructures();
    }

    protected function normalizeStructureState(mixed $value): array
    {
        return app(PersonnelListStateNormalizer::class)->normalizeStructure($value);
    }

    protected function normalizeFilterPayload(array $filters): array
    {
        return app(PersonnelListStateNormalizer::class)->normalizeFilters($filters);
    }

    /**
     * @return array{
     *     can_edit: bool,
     *     can_delete: bool,
     *     can_view_portfolio: bool,
     *     can_manage_my_hr_accounts: bool,
     *     can_manage_onboarding_documents: bool,
     *     can_manage_learning_materials: bool
     * }
     */
    protected function rowActionCapabilities(): array
    {
        if ($this->rowActionCapabilities !== null) {
            return $this->rowActionCapabilities;
        }

        $user = auth()->user();

        return $this->rowActionCapabilities = [
            'can_edit' => $user?->can('edit-personnels') ?? false,
            'can_delete' => $user?->can('delete-personnels') ?? false,
            'can_view_portfolio' => ProfessionalPortfolioPermissionMatrix::canViewPortfolio($user),
            'can_manage_my_hr_accounts' => $user?->can('manage-my-hr-accounts') ?? false,
            'can_manage_onboarding_documents' => ($user?->can('assign-onboarding-documents') ?? false)
                || ($user?->can('manage-onboarding-document-templates') ?? false),
            'can_manage_learning_materials' => ($user?->can('assign-employee-content') ?? false)
                || ($user?->can('manage-employee-content-library') ?? false),
        ];
    }

    public function render()
    {
        return view('personnel::livewire.personnel.table-panel');
    }
}
