<?php

namespace App\Modules\Vacation\Livewire;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Livewire\Traits\SideModalAction;
use App\Models\OrderType;
use App\Models\PersonnelVacation;
use App\Models\Structure;
use App\Modules\Personnel\Contracts\MyHrRequestReview;
use App\Modules\Vacation\Exports\VacationExport;
use App\Services\Chief\ChiefResolver;
use App\Services\NumberToWordsService;
use App\Services\StructureService;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

    #[Url(as: 'type')]
    public $selectedType;

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
        $this->selectedType = null;
        $this->resetPage();
    }

    public function getTableHeaders(): array
    {
        return [
            __('vacation::common.labels.fullname'),
            __('vacation::common.labels.structure'),
            __('vacation::common.labels.type'),
            __('vacation::common.labels.dates'),
            __('vacation::common.labels.duration'),
            __('vacation::common.labels.order'),
            __('personnel::common.labels.action'),
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

        if (! $model->order || ! $model->order->orderType) {
            $this->dispatch('notify', type: 'warning', message: __('vacation::common.messages.order_not_ready'));

            return null;
        }

        // Signatory resolved as-of the order's date: an active temporary delegate
        // (müvəqqəti həvalə) on that date signs in place of the permanent chief, and
        // re-printing an old vacation paper keeps naming whoever was acting then.
        $signatory = app(ChiefResolver::class)->current($model->order_date);
        $chiefName = $signatory['fullname'];
        $chiefRank = $signatory['title'];

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

    public function bindOperationalOrder(PersonnelVacation $model): void
    {
        abort_unless(
            auth()->user()?->can('review-self-service-requests') || auth()->user()?->can('edit-vacations'),
            403
        );

        abort_unless(
            (string) $model->submission_source === 'employee_self_service'
            && (string) $model->approval_status === 'approved'
            && blank($model->order_no),
            422
        );

        app(MyHrRequestReview::class)->bindOperationalVacationOrder($model, auth()->user());

        $this->dispatch('notify', type: 'success', message: __('vacation::common.messages.order_bound'));
    }

    protected function fillFilter(): void
    {
        $this->filter = [
            'vacation_status' => 'all',
            'structure_id' => null,
        ];
    }

    /**
     * The visibility-scoped, filtered vacation query every read shares — the table,
     * the panel status counts and the panel type counts. It deliberately leaves the
     * status bucket out so the counts can be computed per bucket.
     *
     * @return Builder<PersonnelVacation>
     */
    protected function baseQuery(): Builder
    {
        return PersonnelVacation::query()
            ->whereHas('personnel', fn ($query) => $query->whereIn('structure_id', $this->accessibleStructureIds))
            ->where(function ($query) {
                // A self-service request only joins the register once it is approved;
                // pending ones live in the review inbox (MyHrOperationalRequestVisibilityTest).
                $query->whereNull('submission_source')
                    ->orWhere(function ($selfService) {
                        $selfService->where('submission_source', '!=', 'employee_self_service')
                            ->orWhere(function ($approved) {
                                $approved->where('submission_source', 'employee_self_service')
                                    ->where('approval_status', 'approved');
                            });
                    });
            })
            ->filter(Arr::except($this->search, ['vacation_status']))
            ->when(
                empty($this->search['date']['min'] ?? null) && empty($this->search['date']['max'] ?? null),
                fn ($query) => $query->whereDateInYear($this->selectedYear)
            );
    }

    /**
     * The base query narrowed to the selected status bucket and vacation type — what
     * the table actually lists.
     *
     * @return Builder<PersonnelVacation>
     */
    protected function scopedQuery(): Builder
    {
        return $this->baseQuery()
            ->filter(Arr::only($this->search, ['vacation_status']))
            ->when($this->selectedType, fn ($query) => $query->whereHas(
                'order',
                fn ($order) => Str::startsWith((string) $this->selectedType, 'tpl:')
                    ? $order->where('template_snapshot->template_code', Str::after((string) $this->selectedType, 'tpl:'))
                    : $order->where('order_type_id', (int) $this->selectedType)
            ));
    }

    protected function returnData($type = 'normal')
    {
        $result = $this->scopedQuery()
            ->with([
                'personnel' => fn ($q) => $q->with([
                    'structure',
                    'position',
                    'latestRank.rank',
                ]),
                'order:id,order_no,order_id,order_type_id,template_snapshot',
                'order.orderType:id,name',
            ])
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

    /**
     * Status bucket counts plus the total vacation days, in one pass — the panel and
     * the header stat strip read the same numbers.
     *
     * @return array{all: int, at_work: int, in_vacation: int, days: int}
     */
    #[Computed]
    public function summary(): array
    {
        $now = Carbon::now();

        $row = $this->baseQuery()
            ->toBase()
            ->selectRaw(
                'count(*) as total,'
                .' sum(case when return_work_date < ? then 1 else 0 end) as at_work,'
                .' sum(case when return_work_date > ? then 1 else 0 end) as in_vacation,'
                .' coalesce(sum(duration), 0) as days',
                [$now, $now]
            )
            ->first();

        return [
            'all' => (int) ($row->total ?? 0),
            'at_work' => (int) ($row->at_work ?? 0),
            'in_vacation' => (int) ($row->in_vacation ?? 0),
            'days' => (int) ($row->days ?? 0),
        ];
    }

    /**
     * Vacation types for the panel. A vacation has no type of its own — it inherits it
     * from the order it was issued under, so the buckets are the linked order's type
     * (legacy block orders) or its frozen Word-template label.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    #[Computed]
    public function typeFilters(): array
    {
        $rows = $this->baseQuery()
            ->toBase()
            ->join('order_logs', 'order_logs.order_no', '=', 'personnel_vacations.order_no')
            ->selectRaw('count(*) as aggregate')
            ->addSelect([
                'order_logs.order_type_id',
                'order_logs.template_snapshot->template_code as template_code',
                'order_logs.template_snapshot->label as template_label',
            ])
            ->groupBy('order_logs.order_type_id', 'order_logs.template_snapshot->template_code', 'order_logs.template_snapshot->label')
            ->get();

        $orderTypeNames = $rows->pluck('order_type_id')->filter()->unique()->isEmpty()
            ? collect()
            : OrderType::query()->whereIn('id', $rows->pluck('order_type_id')->filter()->unique())->pluck('name', 'id');

        // MySQL's json_extract keeps the JSON quoting, SQLite's does not.
        $unquote = fn (?string $value): string => trim((string) $value, '"');

        return $rows
            ->map(function ($row) use ($orderTypeNames, $unquote): ?array {
                if ($row->order_type_id) {
                    return [
                        'key' => (string) $row->order_type_id,
                        'label' => (string) ($orderTypeNames[$row->order_type_id] ?? $row->order_type_id),
                        'count' => (int) $row->aggregate,
                    ];
                }

                $code = $unquote($row->template_code);

                return $code === '' ? null : [
                    'key' => 'tpl:'.$code,
                    'label' => $unquote($row->template_label) ?: $code,
                    'count' => (int) $row->aggregate,
                ];
            })
            ->filter()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function setStatus(string $value): void
    {
        $this->filter['vacation_status'] = $value;
        $this->searchFilter();
        $this->resetPage();
    }

    public function selectType(string $key): void
    {
        $this->selectedType = $key === '' ? null : $key;
        $this->resetPage();
    }

    protected function fillYear(): void
    {
        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', start_date)"
            : 'YEAR(start_date)';

        $this->years = PersonnelVacation::selectRaw("{$yearExpression} as year")
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
        $runtimeCacheKey = md5($search.'|'.($selected ?? 'none'));

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
