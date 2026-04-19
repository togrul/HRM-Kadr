<?php

namespace App\Modules\Candidates\Livewire;

use App\Concerns\LoadsAppealStatuses;
use App\Modules\Candidates\Exports\CandidateExport;
use App\Modules\Candidates\Support\CandidateModeResolver;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use App\Livewire\Traits\SideModalAction;
use App\Models\Setting;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\CandidateDocument;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Services\StructureService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['candidateAdded', 'filterSelected', 'candidateWasDeleted'])]
class CandidateList extends Component
{
    use AuthorizesRequests;
    use LoadsAppealStatuses;
    use InteractsWithRecruitmentPresentation;
    use SideModalAction;
    use WithPagination;

    public array $filter = [];

    public array $search = [];

    #[Url]
    public $status;

    public string $candidateMode = CandidateModeResolver::MILITARY;

    protected ?array $settingsMapCache = null;

    protected array $accessibleStructureIds = [];

    public const SETTINGS_CACHE_KEY = 'candidates:list-settings';

    protected const TEST_SCORE_COLOR_MAP = [
        0 => 'slate',
        1 => 'gray',
        2 => 'rose',
        3 => 'orange',
        4 => 'blue',
        5 => 'green',
    ];

    public function exportExcel()
    {
        $this->authorize('export', Candidate::class);

        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new CandidateExport($report, $this->candidateMode), "candidate-$name.xlsx");
    }

    public function setStatus($newStatus): void
    {
        $this->status = $this->sanitizeStatus($newStatus);
        $this->resetPage();
    }

    public function setDeleteCandidate($candidateId): void
    {
        $this->dispatch('setDeleteCandidate', $candidateId);
    }

    public function searchFilter(): void
    {
        $this->applyFilter();
    }

    public function toggleDocumentCategory(string $category): void
    {
        $this->filter['document_category'] = ($this->search['document_category'] ?? null) === $category ? null : $category;
        $this->applyFilter();
    }

    public function getTableHeaders(): array
    {
        $headers = [
            __('personnel::common.labels.number'),
            __('candidates::common.labels.fullname'),
            __('candidates::common.labels.structure'),
            __('candidates::common.labels.dates'),
            __('candidates::common.labels.status'),
            __('candidates::common.labels.files'),
            __('personnel::common.labels.action'),
            __('personnel::common.labels.action'),
        ];

        if ($this->isMilitaryCandidateMode()) {
            array_splice($headers, 3, 0, [__('candidates::common.labels.tests')]);
        }

        return $headers;
    }

    public function applyFilter(array $filter = []): void
    {
        $this->search = $this->sanitizeFilterForMode($filter ?: $this->filter);
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->filter = [];
        $this->applyFilter([]);
    }

    public function restoreData($id): void
    {
        $candidate = Candidate::withTrashed()->findOrFail($id);
        $this->authorize('restore', $candidate);
        $candidate->restore();
        $candidate->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('candidateAdded', __('candidates::common.messages.candidate_updated'));
    }

    public function forceDeleteData($id): void
    {
        $model = Candidate::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $model);
        $model->forceDelete();
        $this->dispatch('candidateWasDeleted', __('candidates::common.messages.candidate_deleted'));
    }

    protected function filteredCandidateQuery(): Builder
    {
        return Candidate::query()
            ->when(
                ! empty($this->accessibleStructureIds),
                fn ($query) => $query->whereIn('structure_id', $this->accessibleStructureIds)
            )
            ->when(is_numeric($this->status), fn ($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->filter($this->search ?? []);
    }

    protected function returnData($type = 'normal')
    {
        $result = $this->filteredCandidateQuery()
            ->with([
                'structure',
                'status',
                'creator',
                'personDidDelete',
                'latestApplication' => fn ($query) => $query->select([
                    'candidate_applications.id',
                    'candidate_applications.candidate_id',
                    'candidate_applications.job_opening_id',
                    'candidate_applications.current_stage',
                    'candidate_applications.status',
                ]),
                'latestApplication.opening:id,title,job_requisition_id',
                'latestApplication.opening.requisition:id,title',
            ])
            ->withCount('documents')
            ->orderByDesc('appeal_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(15)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Candidate $candidate, int $index) use ($start) {
                $candidate->row_no = $start + $index + 1;
                $candidate->knowledge_test_color = self::TEST_SCORE_COLOR_MAP[(int) ($candidate->knowledge_test ?? 0)] ?? 'slate';
                $candidate->physical_fitness_exam_color = self::TEST_SCORE_COLOR_MAP[(int) ($candidate->physical_fitness_exam ?? 0)] ?? 'slate';

                return $candidate;
            })
        );

        return $paginated;
    }

    #[Computed]
    public function candidateRows(): LengthAwarePaginator
    {
        return $this->returnData();
    }

    #[Computed]
    public function appealStatusTabs(): Collection
    {
        return $this->visibleAppealStatuses();
    }

    #[Computed]
    public function canShowDeletedTab(): bool
    {
        return $this->showDeletedTab();
    }

    public function mount(StructureService $structureService): void
    {
        $this->authorize('viewAny', Candidate::class);
        $this->candidateMode = app(CandidateModeResolver::class)->resolve();
        $this->status = $this->sanitizeStatus(request()->query('status', $this->defaultStatus()));
        $this->filter = $this->sanitizeFilterForMode($this->filter);
        $this->search = $this->sanitizeFilterForMode($this->search);
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    public function render()
    {
        return view('candidates::livewire.candidates.candidate-list');
    }

    #[Computed]
    public function recruitmentSummary(): array
    {
        return Cache::remember('candidates:recruitment-summary', now()->addMinute(), function (): array {
            $summary = DB::query()
                ->selectSub(JobRequisition::query()->selectRaw('COUNT(*)'), 'requisitions')
                ->selectSub(JobOpening::query()->selectRaw('COUNT(*)'), 'openings')
                ->selectSub(CandidateApplication::query()->selectRaw('COUNT(*)'), 'applications')
                ->selectSub(CandidateApplication::query()->where('status', 'active')->selectRaw('COUNT(*)'), 'active_applications')
                ->first();

            return [
                'requisitions' => (int) ($summary->requisitions ?? 0),
                'openings' => (int) ($summary->openings ?? 0),
                'applications' => (int) ($summary->applications ?? 0),
                'active_applications' => (int) ($summary->active_applications ?? 0),
            ];
        });
    }

    #[Computed]
    public function documentCategoryOptions(): array
    {
        return collect((array) config('candidates.documents.categories', ['other']))
            ->map(fn (string $category) => [
                'id' => $category,
                'label' => __('candidates::files.categories.'.$category),
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function documentCategoryStats(): Collection
    {
        $candidateScope = $this->filteredCandidateQuery()->select('candidates.id');
        $activeCategory = $this->search['document_category'] ?? null;
        $stats = CandidateDocument::query()
            ->select([
                'category',
                DB::raw('COUNT(*) as documents_count'),
                DB::raw('COUNT(DISTINCT candidate_id) as candidates_count'),
            ])
            ->whereIn('candidate_id', $candidateScope)
            ->groupBy('category')
            ->orderByDesc('documents_count')
            ->get()
            ->keyBy('category');

        return collect($this->documentCategoryOptions)
            ->map(function (array $option) use ($stats, $activeCategory) {
                $stat = $stats->get($option['id']);

                return [
                    'id' => $option['id'],
                    'label' => $option['label'],
                    'documents_count' => (int) ($stat->documents_count ?? 0),
                    'candidates_count' => (int) ($stat->candidates_count ?? 0),
                    'active' => $activeCategory === $option['id'],
                ];
            });
    }

    public function isMilitaryCandidateMode(): bool
    {
        return $this->candidateMode === CandidateModeResolver::MILITARY;
    }

    private function sanitizeFilterForMode(array $filter): array
    {
        $allowedTopLevel = array_flip($this->enabledFilterKeys());

        $sanitized = array_intersect_key($filter, $allowedTopLevel);

        if (! $this->isMilitaryCandidateMode()) {
            unset($sanitized['results']);
        }

        return $sanitized;
    }

    public function filterEnabled(string $key): bool
    {
        return in_array($key, $this->enabledFilterKeys(), true);
    }

    private function listPreset(): array
    {
        return $this->presetForMode($this->candidateMode);
    }

    private function presetForMode(string $mode): array
    {
        $preset = (array) config("candidates.list_presets.{$mode}", []);
        $settingsMap = $this->settingsMap();

        $settingKeys = [
            'status_whitelist' => "candidates.list_presets.{$mode}.status_whitelist",
            'default_status' => "candidates.list_presets.{$mode}.default_status",
            'show_deleted_tab' => "candidates.list_presets.{$mode}.show_deleted_tab",
            'enabled_filters' => "candidates.list_presets.{$mode}.enabled_filters",
        ];

        if (array_key_exists($settingKeys['status_whitelist'], $settingsMap)) {
            $preset['status_whitelist'] = $this->normalizeStatusWhitelist($settingsMap[$settingKeys['status_whitelist']]);
        }

        if (array_key_exists($settingKeys['default_status'], $settingsMap)) {
            $preset['default_status'] = $this->normalizeDefaultStatus($settingsMap[$settingKeys['default_status']]);
        }

        if (array_key_exists($settingKeys['show_deleted_tab'], $settingsMap)) {
            $preset['show_deleted_tab'] = $this->normalizeBoolean($settingsMap[$settingKeys['show_deleted_tab']]);
        }

        if (array_key_exists($settingKeys['enabled_filters'], $settingsMap)) {
            $preset['enabled_filters'] = $this->normalizeEnabledFilters($settingsMap[$settingKeys['enabled_filters']]);
        }

        return $preset;
    }

    private function resolveEnabledFilters(): array
    {
        $configured = (array) ($this->listPreset()['enabled_filters'] ?? []);

        if ($configured !== []) {
            return array_values(array_unique(array_map('strval', $configured)));
        }

        return $this->isMilitaryCandidateMode()
            ? ['fullname', 'gender', 'results', 'age', 'appeal_date', 'document_category']
            : ['fullname', 'gender', 'age', 'appeal_date', 'document_category'];
    }

    private function resolveVisibleStatusIds(Collection $statuses): array
    {
        $all = $statuses->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $whitelist = array_values(array_unique(array_map('intval', (array) ($this->listPreset()['status_whitelist'] ?? []))));

        if ($whitelist === []) {
            return $all;
        }

        return array_values(array_intersect($all, $whitelist));
    }

    private function settingsMap(): array
    {
        if (is_array($this->settingsMapCache)) {
            return $this->settingsMapCache;
        }

        return $this->settingsMapCache = Cache::rememberForever(self::SETTINGS_CACHE_KEY, static fn () => Setting::pluck('value', 'name')->toArray());
    }

    private function normalizeStatusWhitelist(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_map('intval', $value)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_unique(array_map('intval', $decoded)));
            }

            $parts = array_filter(array_map('trim', explode(',', $value)), static fn ($v) => $v !== '');

            return array_values(array_unique(array_map('intval', $parts)));
        }

        return [];
    }

    private function normalizeDefaultStatus(mixed $value): string|int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $normalized = trim($value);

            if ($normalized === 'all' || $normalized === 'deleted') {
                return $normalized;
            }

            if (is_numeric($normalized)) {
                return (int) $normalized;
            }
        }

        return 'all';
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function normalizeEnabledFilters(mixed $value): array
    {
        $allowed = ['fullname', 'gender', 'results', 'age', 'appeal_date', 'document_category'];
        $raw = [];

        if (is_array($value)) {
            $raw = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_filter(array_map('trim', explode(',', $value)), static fn (string $v) => $v !== '');
            }
        }

        return array_values(array_unique(array_intersect(array_map('strval', $raw), $allowed)));
    }

    private function visibleAppealStatuses(): Collection
    {
        $visibleStatusIds = $this->visibleStatusIds();

        $visible = $this->appealStatuses()->filter(
            fn ($status) => in_array((int) $status->id, $visibleStatusIds, true)
        )->values();

        return $visible;
    }

    private function visibleStatusIds(): array
    {
        return $this->resolveVisibleStatusIds($this->appealStatuses());
    }

    private function enabledFilterKeys(): array
    {
        return $this->resolveEnabledFilters();
    }

    private function showDeletedTab(): bool
    {
        return (bool) ($this->listPreset()['show_deleted_tab'] ?? true);
    }

    private function defaultStatus()
    {
        $visibleStatusIds = $this->visibleStatusIds();
        $default = $this->listPreset()['default_status'] ?? 'all';

        if (is_numeric($default)) {
            $id = (int) $default;

            return in_array($id, $visibleStatusIds, true) ? $id : 'all';
        }

        if ($default === 'deleted') {
            return $this->showDeletedTab() ? 'deleted' : 'all';
        }

        return 'all';
    }

    private function sanitizeStatus($status)
    {
        $visibleStatusIds = $this->visibleStatusIds();

        if (is_numeric($status)) {
            $id = (int) $status;

            return in_array($id, $visibleStatusIds, true) ? $id : $this->defaultStatus();
        }

        if ($status === 'deleted') {
            return $this->showDeletedTab() ? 'deleted' : $this->defaultStatus();
        }

        if ($status === 'all') {
            return 'all';
        }

        return $this->defaultStatus();
    }
}
