<?php

namespace App\Modules\BusinessTrips\Livewire;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\OrderType;
use App\Models\PersonnelBusinessTrip;
use App\Models\Structure;
use App\Modules\BusinessTrips\Exports\BusinessTripExport;
use App\Services\StructureService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;

class BusinessTrips extends Component
{
    use AuthorizesRequests;
    use DropdownConstructTrait;
    use WithPagination;

    public array $filter = [];

    public array $search = [];

    public $searchStructure;

    #[Locked]
    public array $accessibleStructureIds = [];

    #[Url]
    public $status;

    #[Url(as: 'location')]
    public $selectedLocation;

    public function exportExcel()
    {
        $this->authorize('export', PersonnelBusinessTrip::class);
        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new BusinessTripExport($report), "businessTrips-{$name}.xlsx");
    }

    public function searchFilter()
    {
        $this->search = $this->filter;
    }

    public function resetFilter()
    {
        $this->fillFilter();
        $this->search = $this->filter;
        $this->selectedLocation = null;
        $this->resetPage();
    }

    public function setStatus(string $value): void
    {
        $this->filter['business_trip_status'] = $value;
        $this->searchFilter();
        $this->resetPage();
    }

    public function selectLocation(string $value): void
    {
        $this->selectedLocation = $value === '' ? null : $value;
        $this->resetPage();
    }

    protected function fillFilter()
    {
        $this->filter = [
            'structure_id' => null,
            'order_type_id' => null,
            'business_trip_status' => 'all',
        ];
    }

    public function getTableHeaders(): array
    {
        return [
            __('business_trips::common.table.fullname'),
            __('business_trips::common.table.dates'),
            __('business_trips::common.table.locations'),
            __('business_trips::common.table.order'),
            'action',
        ];
    }

    public function printBusinessTripDocument(PersonnelBusinessTrip $model, $multi = false)
    {
        $model->load(['personnel', 'order.orderType', 'order.attributes', 'personnel.idDocuments']);

        if (! $model->order || ! $model->order->orderType) {
            $this->dispatch('notify', type: 'warning', message: __('business_trips::common.messages.order_not_ready'));

            return null;
        }

        $filepath = $multi
            ? '/storage/templates/general/Ezamiyyet-vesiqesi.docx'
            : '/storage/templates/general/Ezamiyyet-kagizi.docx';

        $file = public_path($filepath);

        $templateProcessor = new TemplateProcessor($file);

        $dates = [
            'givenDate' => Carbon::parse($model->order_date),
            'startDate' => Carbon::parse($model->order->description['start_date']),
            'endDate' => Carbon::parse($model->order->description['end_date']),
        ];

        $tripDuration = $dates['startDate']->diffInDays($dates['endDate']);
        $formattedDates = array_map(function ($date) {
            return [
                'day' => $date->format('d'),
                'month' => strtolower($date->locale('AZ')->monthName),
                'year' => $date->format('y'),
            ];
        }, $dates);

        $attributes = $model->order->attributes->toArray();

        $templateProcessor->setValue('orderno', $model->order_no);
        $templateProcessor->setValue('day', $formattedDates['givenDate']['day']);
        $templateProcessor->setValue('month', $formattedDates['givenDate']['month']);
        $templateProcessor->setValue('year', $formattedDates['givenDate']['year']);
        $templateProcessor->setValue('duration', $tripDuration);
        $templateProcessor->setValue('location', $model->order->description['location']);
        $templateProcessor->setValue('start_day', $formattedDates['startDate']['day']);
        $templateProcessor->setValue('start_month', $formattedDates['startDate']['month']);
        $templateProcessor->setValue('start_year', $formattedDates['startDate']['year']);
        $templateProcessor->setValue('end_day', $formattedDates['endDate']['day']);
        $templateProcessor->setValue('end_month', $formattedDates['endDate']['month']);
        $templateProcessor->setValue('end_year', $formattedDates['endDate']['year']);
        if ($multi) {
            //            dd($model->order->description['location']. ' şəhərinə');
            $templateProcessor->cloneRow('rank', count($attributes));
            foreach ($attributes as $index => $row) {
                $templateProcessor->setValue('rank#'.($index + 1), $row['attributes']['$rank']['value']);
                $templateProcessor->setValue('fullname#'.($index + 1), $row['attributes']['$fullname']['value']);
                $templateProcessor->setValue('weapon#'.($index + 1), $row['attributes']['$weapon']['value']);
                $templateProcessor->setValue('bullet#'.($index + 1), $row['attributes']['$bullet']['value'] ?? '32');
            }
        } else {
            //            $suffixService = new WordSuffixService;
            $filteredAttributes = $model->order->attributes->firstWhere('attributes.$fullname.value', $model->personnel->fullname);
            $templateProcessor->setValue('passport', $filteredAttributes->attributes['$passport']['value']) ?? '';
            $templateProcessor->setValue('position', $filteredAttributes->attributes['$position']['value']);
            $templateProcessor->setValue('rank', $filteredAttributes->attributes['$rank']['value']);
            $templateProcessor->setValue('fullname', $filteredAttributes->attributes['$fullname']['value']);
            $templateProcessor->setValue('weapon', $filteredAttributes->attributes['$weapon']['value'] ?? '---------');
            $templateProcessor->setValue('bullet', $filteredAttributes->attributes['$bullet']['value'] ?? '---------');
        }

        $filename = "{$model->personnel->fullname}_ezamiyyet_{$model->start_date->format('d.m.Y')}";
        $templateProcessor->saveAs($filename.'.docx');

        return response()->download($filename.'.docx')->deleteFileAfterSend();
    }

    /**
     * The visibility-scoped, filtered trip query every read shares — the table, the panel
     * status counts and the panel location counts. The status bucket and the selected
     * location are deliberately left out so the counts can be computed per bucket.
     *
     * @return Builder<PersonnelBusinessTrip>
     */
    protected function baseQuery(): Builder
    {
        return PersonnelBusinessTrip::query()
            ->whereHas('personnel', fn ($query) => $query->whereIn('structure_id', $this->accessibleStructureIds))
            ->where(function ($query) {
                // A self-service request only joins the register once it is approved;
                // pending ones live in the review inbox.
                $query->whereNull('submission_source')
                    ->orWhere(function ($selfService) {
                        $selfService->where('submission_source', '!=', 'employee_self_service')
                            ->orWhere(function ($approved) {
                                $approved->where('submission_source', 'employee_self_service')
                                    ->where('approval_status', 'approved');
                            });
                    });
            })
            ->filter(Arr::except($this->search, ['business_trip_status']));
    }

    /**
     * The base query narrowed to the selected status bucket and location.
     *
     * @return Builder<PersonnelBusinessTrip>
     */
    protected function scopedQuery(): Builder
    {
        return $this->baseQuery()
            ->filter(Arr::only($this->search, ['business_trip_status']))
            ->when($this->selectedLocation, fn ($query) => $query->where('location', $this->selectedLocation));
    }

    protected function returnData($type = 'normal')
    {
        $result = $this->scopedQuery()
            ->with([
                'personnel',
                'order.orderType',
                'order.businessTrips:id,order_no',
                'personDidDelete:id,name',
            ])
            ->orderByDesc('end_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(15)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $now = Carbon::now();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (PersonnelBusinessTrip $trip) use ($now) {
                $businessTripsCount = (int) ($trip->order?->businessTrips?->count() ?? 0);
                $isForeign = (int) ($trip->order?->order_type_id ?? 0) === PersonnelBusinessTrip::FOREIGN_BUSINESS_TRIP;
                $trip->is_multi_order_trip = $businessTripsCount > 1 && ! $isForeign;

                $startDate = Carbon::parse($trip->start_date);
                $endDate = Carbon::parse($trip->end_date);
                $trip->is_active_trip = $startDate <= $now && $endDate > $now;
                $trip->start_date_label = $startDate->format('d.m.Y');
                $trip->end_date_label = $endDate->format('d.m.Y');
                $trip->order_date_label = Carbon::parse($trip->order_date)->format('d.m.Y');

                if ($trip->deleted_at) {
                    $trip->deleted_at_label = Carbon::parse($trip->deleted_at)->format('d.m.Y H:i');
                }

                return $trip;
            })
        );

        return $paginated;
    }

    #[Computed]
    public function businessTrips()
    {
        return $this->returnData();
    }

    /**
     * Status bucket counts for the panel and the pagination caption, in one pass.
     *
     * @return array{all: int, at_work: int, in_business_trip: int, deleted: int}
     */
    #[Computed]
    public function summary(): array
    {
        $today = Carbon::now()->format('Y-m-d');

        $row = $this->baseQuery()
            ->toBase()
            ->selectRaw(
                'count(*) as total,'
                .' sum(case when end_date < ? then 1 else 0 end) as at_work,'
                .' sum(case when end_date >= ? then 1 else 0 end) as in_business_trip',
                [$today, $today]
            )
            ->first();

        return [
            'all' => (int) ($row->total ?? 0),
            'at_work' => (int) ($row->at_work ?? 0),
            'in_business_trip' => (int) ($row->in_business_trip ?? 0),
            'deleted' => $this->baseQuery()->onlyTrashed()->count(),
        ];
    }

    /**
     * How many distinct people the CURRENT list has away right now — the second half of
     * the pagination caption, so both of its numbers describe the same scope.
     */
    #[Computed]
    public function scopedPeopleAway(): int
    {
        $today = Carbon::now()->format('Y-m-d');

        return $this->scopedQuery()
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->distinct()
            ->count('tabel_no');
    }

    /**
     * Destinations for the panel, most-visited first. `location` is a free-text column,
     * so the buckets are whatever was actually typed on the orders.
     *
     * @return array<int, array{key: string, count: int}>
     */
    #[Computed]
    public function locationFilters(): array
    {
        return $this->baseQuery()
            ->toBase()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->selectRaw('location, count(*) as aggregate')
            ->groupBy('location')
            ->orderByDesc('aggregate')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => ['key' => (string) $row->location, 'count' => (int) $row->aggregate])
            ->all();
    }

    public function mount(StructureService $structureService)
    {
        $this->authorize('viewAny', PersonnelBusinessTrip::class);
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
        $this->fillFilter();
    }

    public function render()
    {
        return view('business-trips::livewire.business-trips.business-trips');
    }

    #[Computed]
    public function structureOptions(): array
    {
        $search = $this->dropdownSearch('searchStructure');

        $base = Structure::query()
            ->select('id', DB::raw('name as label'))
            ->accessible()
            ->orderBy('level')
            ->orderBy('code');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'businessTrips:structures',
                base: $base,
                selectedId: $this->filter['structure_id'] ?? null,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $this->filter['structure_id'] ?? null,
            limit: 80
        );
    }

    #[Computed]
    public function orderTypeOptions(): array
    {
        $base = OrderType::query()
            ->select('id', DB::raw('name as label'))
            ->where('order_id', 3010)
            ->orderBy('name');

        return $this->cachedOptionsWithSelected(
            'businessTrips:order_types',
            $base,
            $this->filter['order_type_id'] ?? null,
            50
        );
    }
}
