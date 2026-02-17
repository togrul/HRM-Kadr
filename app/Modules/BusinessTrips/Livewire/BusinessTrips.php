<?php

namespace App\Modules\BusinessTrips\Livewire;

use App\Modules\BusinessTrips\Exports\BusinessTripExport;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\OrderType;
use App\Models\PersonnelBusinessTrip;
use App\Models\Structure;
use App\Services\StructureService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
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
        return [__('#'), __('Fullname'), __('Dates'), __('Locations'), __('Order'), 'action'];
    }

    public function printBusinessTripDocument(PersonnelBusinessTrip $model, $multi = false)
    {
        $model->load(['personnel', 'order.orderType', 'order.attributes', 'personnel.idDocuments']);
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
                $templateProcessor->setValue('rank#' . ($index + 1), $row['attributes']['$rank']['value']);
                $templateProcessor->setValue('fullname#' . ($index + 1), $row['attributes']['$fullname']['value']);
                $templateProcessor->setValue('weapon#' . ($index + 1), $row['attributes']['$weapon']['value']);
                $templateProcessor->setValue('bullet#' . ($index + 1), $row['attributes']['$bullet']['value'] ?? '32');
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
        $templateProcessor->saveAs($filename . '.docx');

        return response()->download($filename . '.docx')->deleteFileAfterSend();
    }

    protected function returnData($type = 'normal')
    {
        $result = PersonnelBusinessTrip::with([
            'personnel',
            'order.orderType',
            'order.businessTrips:id,order_no',
            'personDidDelete:id,name',
        ])
            ->whereHas('personnel', fn($query) => $query->whereIn('structure_id', $this->accessibleStructureIds))
            ->filter($this->search)
            ->orderByDesc('end_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(15)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();
        $now = Carbon::now();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (PersonnelBusinessTrip $trip, int $index) use ($start, $now) {
                $trip->row_no = $start + $index + 1;

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
