<?php

namespace App\Modules\Vacation\Livewire;

use App\Modules\Vacation\Exports\VacationExport;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Livewire\Traits\SideModalAction;
use App\Models\PersonnelVacation;
use App\Models\Structure;
use App\Services\NumberToWordsService;
use App\Services\StructureService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor;

class Vacations extends Component
{
    use AuthorizesRequests, DropdownConstructTrait, SideModalAction, WithPagination;

    public array $filter = [];

    public array $search = [];

    public string $searchStructure = '';

    #[Locked]
    public array $accessibleStructureIds = [];

    #[Url]
    public $status;

    public $years = [];

    #[Url(as: 'year', keep: true)]
    public $selectedYear;

    protected array $runtimeStructureOptionsCache = [];

    public function exportExcel()
    {
        $this->authorize('export', PersonnelVacation::class);

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

    public function getTableHeaders(): array
    {
        return [
            __('#'),
            __('Fullname'),
            __('Structure'),
            __('Dates'),
            __('Locations'),
            __('Order'),
            'action',
        ];
    }

    public function printVacationDocument(PersonnelVacation $model)
    {
        $model->load([
            'personnel',
            'personnel.latestRank.rank',
            'order',
            'order.orderType',
        ]);

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
                'year' => $year . $suffixService->getNumberSuffix((int) $year),
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
        $templateProcessor->saveAs($filename . '.docx');

        return response()->download($filename . '.docx')->deleteFileAfterSend();
    }

    protected function fillFilter(): void
    {
        $this->filter = [
            'vacation_status' => 'all',
            'structure_id' => null,
        ];
    }

    protected function returnData($type = 'normal')
    {
        $result = PersonnelVacation::with([
            'personnel' => fn($q) => $q->with([
                'structure',
                'position',
                'latestRank.rank',
            ]),
        ])
            ->whereHas('personnel', fn($query) => $query->whereIn('structure_id', $this->accessibleStructureIds))
            ->filter($this->search)
            ->when((empty($this->search['date']['min'] ?? null) && empty($this->search['date']['max'] ?? null)), fn($qq) => $qq->whereDateInYear($this->selectedYear))
            ->orderByDesc('end_date')
            ->orderByDesc('return_work_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(15)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();
        $now = Carbon::now();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (PersonnelVacation $vacation, int $index) use ($start, $now) {
                $vacation->row_no = $start + $index + 1;
                $vacation->is_active_vacation = $vacation->start_date <= $now && $vacation->return_work_date > $now;

                $totalDays = max((int) $vacation->vacation_days_total, 1);
                $remaining = max(0, (int) $vacation->remaining_days);
                $percentage = ($remaining * 100) / $totalDays;

                $vacation->remaining_percentage = $percentage;
                $vacation->remaining_color = match (true) {
                    $percentage < 30 => 'rose',
                    $percentage < 60 => 'blue',
                    default => 'teal',
                };

                return $vacation;
            })
        );

        return $paginated;
    }

    #[Computed]
    public function vacations()
    {
        return $this->returnData();
    }

    protected function fillYear(): void
    {
        $this->years = PersonnelVacation::selectRaw('YEAR(start_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->prepend(Carbon::now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $this->selectedYear = request()->has('year') ? request()->get('year') : $this->years->first();
    }

    public function mount()
    {
        $this->authorize('viewAny', PersonnelVacation::class);
        $this->accessibleStructureIds = resolve(StructureService::class)->getAccessibleStructures();
        $this->fillFilter();
        $this->fillYear();
        if (session()->has('vacation-updated')) {
            $sessionData = session()->pull('vacation-updated');
            if (isset($sessionData['structure_id'])) {
                $sessionData['structure_id'] = $this->normalizeStructureId($sessionData['structure_id']);
            }
            $this->filter = array_merge($this->filter, $sessionData);
            $this->searchFilter();
        }
    }

    public function render()
    {
        return view('vacation::livewire.vacation.vacations');
    }

    #[Computed]
    public function structureOptions(): array
    {
        $search = $this->dropdownSearch('searchStructure');
        $selected = $this->selectedStructureFilterId();
        $runtimeCacheKey = md5($search . '|' . ($selected ?? 'none'));

        if (array_key_exists($runtimeCacheKey, $this->runtimeStructureOptionsCache)) {
            return $this->runtimeStructureOptionsCache[$runtimeCacheKey];
        }

        $query = Structure::query()
            ->select('id', 'name')
            ->accessible()
            ->ordered();

        if ($search === '') {
            $query->limit(120);
        } else {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $options = $query->get()
            ->map(fn ($structure) => [
                'id' => (int) $structure->id,
                'label' => trim((string) $structure->name),
            ])
            ->filter(fn ($option) => $option['label'] !== '')
            ->values();

        if ($selected && $options->firstWhere('id', $selected) === null) {
            if ($selectedStructure = Structure::find($selected)) {
                $options->push([
                    'id' => (int) $selectedStructure->id,
                    'label' => trim((string) $selectedStructure->name),
                ]);
            }
        }

        return $this->runtimeStructureOptionsCache[$runtimeCacheKey] = $options
            ->unique('id')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    protected function selectedStructureFilterId(): ?int
    {
        return $this->normalizeStructureId(data_get($this->filter, 'structure_id'));
    }

    protected function normalizeStructureId($value): ?int
    {
        if (is_array($value)) {
            return isset($value['id']) ? (int) $value['id'] : null;
        }

        if ($value === '' || $value === null) {
            return null;
        }

        return (int) $value;
    }
}
