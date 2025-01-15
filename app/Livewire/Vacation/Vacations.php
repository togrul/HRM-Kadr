<?php

namespace App\Livewire\Vacation;

use App\Exports\VacationExport;
use App\Livewire\Traits\SelectListTrait;
use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Models\Structure;
use App\Services\NumberToWordsService;
use App\Services\StructureService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;

class Vacations extends Component
{
    use AuthorizesRequests,SelectListTrait,SideModalAction,WithPagination;

    public array $filter = [];

    public array $search = [];

    public $searchStructure;

    #[Url]
    public $status;

    public function exportExcel()
    {
        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new VacationExport($report), "vacation-{$name}.xlsx");
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

    public function printVacationDocument(PersonnelVacation $model)
    {
        $model->load(['personnel', 'personnel.latestRank.rank', 'order', 'order.orderType']);

//        $chief = Personnel::with(['latestRank.rank'])
//            ->where(['structure_id' => 8, 'position_id' => 10])
//            ->active()
//            ->firstOrFail();
        $chiefName = cache('settings')['Chief'];
        $chiefRank = cache('settings')['Chief rank'];

        $dates = [
            'givenDate' => Carbon::parse($model->order_date),
            'startDate' => Carbon::parse($model->start_date),
            'endDate' => Carbon::parse($model->end_date),
            'returnWorkDate' => Carbon::parse($model->return_work_date),
        ];

        $file = public_path('/storage/templates/general/Mezuniyyət-kagizi.docx');

        $suffixService = new WordSuffixService;
        $formattedDates = array_map(function ($date) use ($suffixService) {
            $year = $date->format('Y');

            return [
                'day' => $date->format('d'),
                'month' => $date->locale('AZ')->monthName,
                'year' => $year.$suffixService->getNumberSuffix((int) $year),
            ];
        }, $dates);

        $templateProcessor = new TemplateProcessor($file);

        $templateProcessor->setValue('order_no', $model->order_no);
        $templateProcessor->setValue('day', $formattedDates['givenDate']['day']);
        $templateProcessor->setValue('month', $formattedDates['givenDate']['month']);
        $templateProcessor->setValue('year', $formattedDates['givenDate']['year']);
        $templateProcessor->setValue('rank', $model->personnel->latestRank?->rank?->name);
        $templateProcessor->setValue('fullname', $model->personnel->fullname_max);
        $templateProcessor->setValue('vacation_type', Str::lower($model->order->orderType->name));
        $templateProcessor->setValue('vacation_place', $model->vacation_places);
        $templateProcessor->setValue('days', $model->duration);
        $templateProcessor->setValue('spell', resolve(NumberToWordsService::class)->convert($model->duration));
        $templateProcessor->setValue('start_day', $formattedDates['startDate']['day']);
        $templateProcessor->setValue('start_month', $formattedDates['startDate']['month']);
        $templateProcessor->setValue('start_year', $formattedDates['startDate']['year']);
        $templateProcessor->setValue('end_day', $formattedDates['endDate']['day']);
        $templateProcessor->setValue('end_month', $formattedDates['endDate']['month']);
        $templateProcessor->setValue('end_year', $formattedDates['endDate']['year']);
        $templateProcessor->setValue('work_day', $formattedDates['returnWorkDate']['day']);
        $templateProcessor->setValue('work_month', $formattedDates['returnWorkDate']['month']);
        $templateProcessor->setValue('work_year', $formattedDates['returnWorkDate']['year']);
        $templateProcessor->setValue('rank_signature', $chiefRank);
        $templateProcessor->setValue('person_signature', $chiefName);

        $filename = "{$model->personnel->fullname}_mezuniyyet_{$model->start_date->format('d.m.Y')}";
        $templateProcessor->saveAs($filename.'.docx');

        return response()->download($filename.'.docx')->deleteFileAfterSend();
    }

    protected function fillFilter(): void
    {
        $this->filter = [
            'vacation_status' => 'all',
        ];
    }

    protected function returnData($type = 'normal')
    {
        $result = PersonnelVacation::with(['personnel', 'personnel.structure', 'personnel.position', 'personnel.latestRank.rank'])
            ->filter($this->search)
            ->whereHas('personnel', fn ($query) => $query->whereIn('structure_id', resolve(StructureService::class)->getAccessibleStructures()))
            ->orderByDesc('end_date')
            ->orderByDesc('return_work_date');

        return $type == 'normal'
            ? $result->paginate(15)->withQueryString()
            : $result->get()->toArray();
    }

    #[Computed()]
    public function vacations()
    {
        return $this->returnData();
    }

    public function mount()
    {
        $this->authorize('show-vacations');
        $this->fillFilter();
    }

    public function render()
    {
        $_structures = Structure::when(! empty($this->searchStructure), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchStructure}%");
        })
            ->accessible()
            ->ordered()
            ->get();

        return view('livewire.vacation.vacations', compact('_structures'));
    }
}
