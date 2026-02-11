<?php

namespace App\Modules\Personnel\Livewire;

use App\Modules\Personnel\Exports\PersonnelExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Models\Position;
use App\Services\StructureService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['personnelAdded', 'fileAdded', 'personnelWasDeleted'])]
class AllPersonnel extends Component
{
    use AuthorizesRequests;
    use NestedStructureTrait;
    use SideModalAction;
    use WithPagination;

    #[Url]
    public ?string $status = null;

    #[Url]
    public array $filters = [];

    #[Url]
    public array|string $structure = '';

    #[Url(as: 'position')]
    public ?int $selectedPosition = null;

    public bool $filterMounted = false;

    public bool $openFilterOnMount = false;

    protected ?array $accessibleStructureCache = null;

    protected ?Collection $positionCache = null;

    protected function queryString()
    {
        return [
            'structure' => [
                'compact' => ',',
            ],
        ];
    }

    public function exportExcel()
    {
        $this->authorize('export', Personnel::class);

        $report['data'] = $this->returnData(type: 'excel');
        $report['filter'] = $this->filters;
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new PersonnelExport($report), "personnel-$name.xlsx");
    }

    public function printPage($personnel, $headers = null): void
    {
        $headers = [__('#'), __('Tabel'), __('Fullname'), __('Gender'), __('Position'), 'action', 'action', 'action', 'action'];
        redirect()->route('print.page', ['model' => $personnel, 'headers' => $headers]);
    }

    #[On('filterSelected')]
    public function filterSelected(array $filter)
    {
        $this->filters = $filter;
        $this->reset(['structure']);
    }

    public function openFilter(): void
    {
        if (! $this->filterMounted) {
            $this->filterMounted = true;
            $this->openFilterOnMount = true;

            return;
        }

        $this->dispatch('setOpenFilter');
    }

    #[On('openFilterWasSet')]
    public function consumeFilterAutoOpenFlag(): void
    {
        $this->openFilterOnMount = false;
    }

    public function setDeletePersonnel($personnelId)
    {
        $this->dispatch('setDeletePersonnel', $personnelId);
    }

    public function restoreData($id)
    {
        $personnel = Personnel::withTrashed()->where('tabel_no', $id)->first();

        if (! $personnel) {
            return;
        }

        $this->authorize('restore', $personnel);

        $personnel->restore();
        $personnel->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('personnelAdded', __('Personnel was updated successfully!'));
    }

    public function forceDeleteData($id)
    {
        $model = Personnel::withTrashed()->where('tabel_no', $id)->first();

        if (! $model) {
            return;
        }

        $this->authorize('forceDelete', $model);

        $model->forceDelete();
        $this->dispatch('personnelWasDeleted', __('Personnel was deleted!'));
    }

    #[On('selectStructure')]
    public function selectStructure($id)
    {
        $this->structure = $this->getNestedStructure($id);
    }

    public function getStatusFilters(): array
    {
        return [
            ['key' => 'current', 'label' => __('Active')],
            ['key' => 'leaves', 'label' => __('Resigned')],
            ['key' => 'all', 'label' => __('All')],
            ['key' => 'deleted', 'label' => __('Deleted'), 'permission' => 'access-admin'],
            ['key' => 'pending', 'label' => __('Pending')],
        ];
    }

    public function getTableHeaders(): array
    {
        return [
            __('#'),
            __('Tabel'),
            __('Fullname'),
            __('Structure'),
            __('Date'),
            'action',
        ];
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function setPosition($new)
    {
        $this->selectedPosition = $new;
        $this->resetPage();
    }

    public function resetFilter()
    {
        $this->reset('selectedPosition');
        $this->resetPage();
    }

    public function resetSelectedFilter()
    {
        $this->filters = [];
        $this->resetPage();
        $this->fillFilter();
        $this->dispatch('filterResetted');
    }

    public function fillFilter()
    {
        $this->status = request()->query('status', 'current');
        $this->filters ??= [];
    }

    #[Computed]
    public function personnels()
    {
        return $this->returnData();
    }

    #[Computed(persist: true)]
    public function positions()
    {
        return $this->positionCache
            ??= Cache::remember(
                'personnel:positions:list',
                now()->addMinutes(30),
                function () {
                    return Position::query()
                        ->select('id', 'name')
                        ->orderBy('id')
                        ->get();
                }
            );
    }

    public function mount()
    {
        $this->authorize('viewAny', Personnel::class);
        $this->fillFilter();
        $this->filters = $this->filters ?? [];
        $this->filterMounted = false;
        $this->openFilterOnMount = false;
    }

    protected function returnData($type = 'normal')
    {
        $query = $this->personnelQuery();

        return $type == 'normal'
            ? $query->paginate(10)->withQueryString()
            : $query->cursor();
    }

    protected function personnelQuery(): Builder
    {
        $structureIds = $this->selectedStructureIds();

        $locale = app()->getLocale();

        $relations = [
            'latestRank',
            "latestRank.rank:id,name_{$locale}",
            'latestVacation',
            'latestBusinessTrip',
            'position:id,name',
            'currentWork',
            'latestDisposal',
        ];

        if ($this->status === 'deleted') {
            $relations[] = 'personDidDelete:id,name';
        }

        $builder = Personnel::query()
            ->select([
                'personnels.id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'personnels.photo',
                'personnels.gender',
                'personnels.structure_id',
                'personnels.position_id',
                'personnels.join_work_date',
                'personnels.leave_work_date',
                'personnels.is_pending',
                'personnels.deleted_at',
                'personnels.deleted_by',
            ])
            ->leftJoin('positions as position_sort', 'position_sort.id', '=', 'personnels.position_id')
            ->leftJoin('structures as structure_sort', 'structure_sort.id', '=', 'personnels.structure_id')
            ->with($relations)
            ->withStructureTree()

            ->when(! empty($structureIds), function (Builder $query) use ($structureIds) {
                $query->whereIn('personnels.structure_id', $structureIds);
            }, function (Builder $query) {
                $query->whereIn('personnels.structure_id', $this->accessibleStructureIds());
            })
            ->when(! empty($this->selectedPosition), function (Builder $query) {
                $query->where('personnels.position_id', $this->selectedPosition);
            })
            ->when($this->status, function (Builder $query) {
                switch ($this->status) {
                    case 'current':
                        $query->whereNull('personnels.leave_work_date');
                        break;
                    case 'leaves':
                        $query->whereNotNull('personnels.leave_work_date');
                        break;
                    case 'deleted':
                        $query->onlyTrashed();
                        break;
                    case 'pending':
                        $query->where('personnels.is_pending', true);
                        break;
                    default:
                        $query->where('personnels.is_pending', false);
                }
            })
            ->when(! empty($this->filters), fn ($q) => $q->filter($this->filters))
            ->orderBy('position_sort.name')
            ->orderBy('structure_sort.name');

        return $builder;
    }

    protected function accessibleStructureIds(): array
    {
        if (! is_null($this->accessibleStructureCache)) {
            return $this->accessibleStructureCache;
        }

        return $this->accessibleStructureCache = resolve(StructureService::class)->getAccessibleStructures();
    }

    protected function selectedStructureIds(): array
    {
        if (is_array($this->structure)) {
            return array_filter(array_map('intval', $this->structure));
        }

        if (is_string($this->structure)) {
            $value = trim($this->structure);
            if ($value === '') {
                return [];
            }

            return array_filter(array_map('intval', explode(',', $value)));
        }

        return [];
    }

    public function render()
    {
        return view('personnel::livewire.personnel.all-personnel');
    }
}
