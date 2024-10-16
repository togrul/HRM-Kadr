<?php

namespace App\Livewire\Outside;

use App\Exports\BusinessTripExport;
use App\Livewire\Traits\SelectListTrait;
use App\Models\OrderType;
use App\Models\PersonnelBusinessTrip;
use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class BusinessTrips extends Component
{
    use AuthorizesRequests;
    use SelectListTrait;
    use WithPagination;

    public array $filter = [];

    public array $search = [];

    public $searchStructure;

    #[Url]
    public $status;

    public function exportExcel()
    {
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
            'business_trip_status' => 'all',
        ];
    }

    public function printBusinessTripDocument(PersonnelBusinessTrip $model, $multi = false)
    {
        dd($model);
//        1 adamirsa onda tekli kagiz diger hallarda multi
        $model->load(['personnel', 'order.orderType']);

        $dates = [
            'givenDate' => Carbon::parse($model->order_date),
            'startDate' => Carbon::parse($model->start_date),
            'endDate' => Carbon::parse($model->end_date),
        ];
    }

    protected function returnData($type = 'normal')
    {
        $result = PersonnelBusinessTrip::with(['personnel', 'order.orderType'])
            ->filter($this->search)
            ->orderByDesc('end_date');

        return $type == 'normal'
            ? $result->paginate(15)->withQueryString()
            : $result->get()->toArray();
    }

    #[Computed()]
    public function businessTrips()
    {
        return $this->returnData();
    }

    public function mount()
    {
        $this->fillFilter();
    }

    public function render()
    {
        $_structures = Structure::when(! empty($this->searchStructure), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchStructure}%");
        })->get();

        $_order_types = OrderType::where('order_id', 3010)->get();

        return view('livewire.outside.business-trips', compact('_structures', '_order_types'));
    }
}
