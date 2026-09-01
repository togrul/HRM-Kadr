<?php

namespace App\Modules\Audit\Livewire;

use App\Models\AttendanceOvertimeRequest;
use App\Models\AuditActivity;
use App\Models\Candidate;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\StaffSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogDashboard extends Component
{
    use WithPagination;

    public string $search = '';

    public string $logName = '';

    public string $event = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 25;

    public ?int $selectedActivityId = null;

    /**
     * @var array<string,string>
     */
    public array $actorLabels = [];

    /**
     * @var array<string,string>
     */
    public array $subjectLabels = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('show-audit-logs'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'logName', 'event', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
            $this->selectedActivityId = null;
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->logName = '';
        $this->event = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->perPage = 25;
        $this->selectedActivityId = null;
        $this->resetPage();
    }

    public function selectActivity(int $activityId): void
    {
        $this->selectedActivityId = $activityId;
    }

    public function closeDetail(): void
    {
        $this->selectedActivityId = null;
    }

    public function exportUrl(string $format): string
    {
        return route('audit.logs.export', array_filter([
            'format' => in_array($format, ['csv', 'xlsx'], true) ? $format : 'xlsx',
            'search' => $this->search,
            'log_name' => $this->logName,
            'event' => $this->event,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], fn ($value) => $value !== ''));
    }

    public function render()
    {
        $activities = $this->filteredQuery()
            ->select([
                'id',
                'log_name',
                'description',
                'event',
                'subject_type',
                'subject_id',
                'causer_type',
                'causer_id',
                'properties',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $selectedActivity = $this->selectedActivity();
        $labelActivities = collect($activities->items());

        if ($selectedActivity && ! $labelActivities->contains('id', $selectedActivity->id)) {
            $labelActivities = $labelActivities->push($selectedActivity);
        }

        $this->primeEntityLabels($labelActivities);

        return view('audit::livewire.activity-log-dashboard', [
            'activities' => $activities,
            'selectedActivity' => $selectedActivity,
            'summary' => $this->summary(),
            'logNameOptions' => $this->logNameOptions(),
            'eventCounts' => $this->eventCounts(),
        ]);
    }

    private function filteredQuery(bool $ignoreEvent = false): Builder
    {
        return AuditActivity::query()
            ->when($this->logName !== '', fn (Builder $query) => $query->where('log_name', $this->logName))
            ->when(! $ignoreEvent && $this->event !== '', fn (Builder $query) => $query->where('event', $this->event))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search !== '', function (Builder $query) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($this->search)).'%';

                $query->where(function (Builder $nested) use ($term) {
                    $nested
                        ->where('description', 'like', $term)
                        ->orWhere('event', 'like', $term)
                        ->orWhere('log_name', 'like', $term)
                        ->orWhere('subject_type', 'like', $term)
                        ->orWhere('causer_type', 'like', $term);
                });
            });
    }

    private function selectedActivity(): ?AuditActivity
    {
        if ($this->selectedActivityId === null) {
            return null;
        }

        return AuditActivity::query()->find($this->selectedActivityId);
    }

    /**
     * The header strip's four figures, gathered as conditional aggregates so the whole
     * strip costs one scan instead of four counts.
     *
     * @return array<string,int>
     */
    private function summary(): array
    {
        $startOfDay = today();

        $row = AuditActivity::query()
            ->selectRaw('count(*) as total_count')
            ->selectRaw('sum(case when created_at >= ? and created_at < ? then 1 else 0 end) as today_count', [
                $startOfDay->toDateTimeString(),
                $startOfDay->copy()->addDay()->toDateTimeString(),
            ])
            ->selectRaw('sum(case when event = ? then 1 else 0 end) as profile_opened_count', ['profile_opened'])
            ->selectRaw('count(distinct causer_id) as user_count')
            ->toBase()
            ->first();

        return [
            'total' => (int) ($row->total_count ?? 0),
            'today' => (int) ($row->today_count ?? 0),
            'profile_opened' => (int) ($row->profile_opened_count ?? 0),
            'users' => (int) ($row->user_count ?? 0),
        ];
    }

    private function logNameOptions(): Collection
    {
        return AuditActivity::query()
            ->whereNotNull('log_name')
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->values();
    }

    /**
     * Rows for the panel's HADİSƏ facet: the event and how many entries carry it inside the
     * rest of the current filter. The event filter itself is dropped from the scope —
     * otherwise the selected event is the only one left with a number and the list can
     * never be clicked back out of.
     *
     * @return Collection<string,int>
     */
    private function eventCounts(): Collection
    {
        // The empty key holds the rows that carry no event at all. It stays in the map so
        // "Hamısı" adds up to what clicking it actually lists; the panel skips it as a row,
        // because $event = '' already means "every event".
        return $this->filteredQuery(ignoreEvent: true)
            ->select('event')
            ->selectRaw('count(*) as event_count')
            ->groupBy('event')
            ->orderBy('event')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->event => (int) $row->event_count]);
    }

    public function actorLabel(AuditActivity $activity): string
    {
        if ($activity->causer_id === null) {
            return __('audit::activity.labels.system_actor');
        }

        return $this->actorLabels[$this->entityKey($activity->causer_type, $activity->causer_id)]
            ?? class_basename((string) $activity->causer_type).' #'.$activity->causer_id;
    }

    public function subjectLabel(AuditActivity $activity): string
    {
        $fullname = data_get($activity->properties, 'viewed_personnel_fullname')
            ?: data_get($activity->properties, 'personnel_fullname')
            ?: data_get($activity->properties, 'fullname');

        if (is_string($fullname) && trim($fullname) !== '') {
            return trim($fullname);
        }

        if ($activity->subject_id === null) {
            return __('audit::activity.labels.no_subject');
        }

        return $this->subjectLabels[$this->entityKey($activity->subject_type, $activity->subject_id)]
            ?? class_basename((string) $activity->subject_type).' #'.$activity->subject_id;
    }

    public function eventLabel(?string $event): string
    {
        if (! $event) {
            return __('audit::activity.labels.no_event');
        }

        return $this->translateOr("audit::activity.events.{$this->translationKey($event)}", Str::headline($event));
    }

    public function descriptionLabel(?string $description): string
    {
        if (! $description) {
            return '-';
        }

        $key = match (true) {
            str_starts_with($description, 'You have ') && str_ends_with($description, ' personnel') => 'personnel_'.$this->translationKey(
                Str::between($description, 'You have ', ' personnel')
            ),
            default => match ($description) {
                'User logged in' => 'user_logged_in',
                'User logged out' => 'user_logged_out',
                'Personnel profile opened' => 'personnel_profile_opened',
                'Manual attendance entry created.' => 'manual_entry_created',
                'Manual attendance entry approved.' => 'manual_entry_approved',
                'Manual attendance entry rejected.' => 'manual_entry_rejected',
                'Manual attendance entry updated.' => 'manual_entry_updated',
                'Attendance overtime request created automatically.' => 'attendance_overtime_request_created_automatically',
                'Attendance overtime request created manually.' => 'attendance_overtime_request_created_manually',
                'Attendance overtime request approved.' => 'attendance_overtime_request_approved',
                'Attendance overtime request rejected.' => 'attendance_overtime_request_rejected',
                'Attendance overtime request removed after recalculation.' => 'attendance_overtime_request_removed_after_recalculation',
                'Attendance overtime request recalculated.' => 'attendance_overtime_request_recalculated',
                'Attendance overtime request generation skipped due to missing actor.' => 'attendance_overtime_request_generation_skipped',
                'Duplicate attendance overtime request deleted.' => 'duplicate_attendance_overtime_request_deleted',
                'Attendance calendar created.' => 'attendance_calendar_created',
                'Attendance calendar updated.' => 'attendance_calendar_updated',
                'Attendance calendar deleted.' => 'attendance_calendar_deleted',
                'Attendance settings updated.' => 'attendance_settings_updated',
                'Attendance month closed and locked.' => 'attendance_month_closed_and_locked',
                'Attendance month unlocked.' => 'attendance_month_unlocked',
                'Attendance weekend calendar auto-created.' => 'attendance_weekend_calendar_auto_created',
                default => null,
            },
        };

        return $key ? $this->translateOr("audit::activity.descriptions.{$key}", $description) : $description;
    }

    public function eventTone(?string $event): string
    {
        return match ($event) {
            'login', 'created', 'profile_opened' => 'emerald',
            'logout' => 'sky',
            'updated' => 'amber',
            'deleted', 'force_deleted' => 'rose',
            'restored' => 'sky',
            default => 'zinc',
        };
    }

    /**
     * Tailwind background class for the contextual panel's event dot.
     */
    public function eventDot(?string $event): string
    {
        return match ($this->eventTone($event)) {
            'emerald' => 'bg-[#10b981]',
            'sky' => 'bg-[#0ea5e9]',
            'amber' => 'bg-[#f59e0b]',
            'rose' => 'bg-[#f43f5e]',
            default => 'bg-[#a1a1aa]',
        };
    }

    /**
     * @return array<int,array{key:string,value:string}>
     */
    public function propertyRows(?AuditActivity $activity): array
    {
        if (! $activity) {
            return [];
        }

        $properties = $activity->properties;
        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }

        if (! is_array($properties) || $properties === []) {
            return [];
        }

        return collect($properties)
            ->when(
                $activity->event === 'profile_opened',
                fn (Collection $rows) => $this->normalizeProfileOpenProperties($rows)
            )
            ->map(fn ($value, $key) => [
                'key' => $this->translateOr(
                    "audit::activity.properties.{$this->translationKey((string) $key)}",
                    Str::headline((string) $key)
                ),
                'value' => is_scalar($value) || $value === null
                    ? (string) ($value ?? 'null')
                    : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->values()
            ->all();
    }

    private function normalizeProfileOpenProperties(Collection $properties): Collection
    {
        return $properties
            ->mapWithKeys(function ($value, $key): array {
                $key = (string) $key;

                $normalizedKey = match ($key) {
                    'personnel_id' => 'viewed_personnel_id',
                    'tabel_no' => 'viewed_personnel_tabel_no',
                    'fullname' => 'viewed_personnel_fullname',
                    default => $key,
                };

                return [$normalizedKey => $value];
            });
    }

    private function primeEntityLabels(Collection $activities): void
    {
        $actorKeys = $this->entityKeysFor($activities, 'causer_type', 'causer_id')->flip();
        $subjectKeys = $this->entityKeysFor($activities, 'subject_type', 'subject_id')->flip();
        $labels = $this->labelsFor($activities, [
            ['causer_type', 'causer_id'],
            ['subject_type', 'subject_id'],
        ]);

        $this->actorLabels = array_intersect_key($labels, $actorKeys->all());
        $this->subjectLabels = array_intersect_key($labels, $subjectKeys->all());
    }

    private function entityKeysFor(Collection $activities, string $typeColumn, string $idColumn): Collection
    {
        return $activities
            ->filter(fn (AuditActivity $activity) => filled($activity->{$typeColumn}) && filled($activity->{$idColumn}))
            ->map(fn (AuditActivity $activity) => $this->entityKey($activity->{$typeColumn}, $activity->{$idColumn}))
            ->unique()
            ->values();
    }

    /**
     * @param  array<int,array{0:string,1:string}>  $columnPairs
     * @return array<string,string>
     */
    private function labelsFor(Collection $activities, array $columnPairs): array
    {
        $references = collect($columnPairs)
            ->flatMap(fn (array $columns) => $activities->map(function (AuditActivity $activity) use ($columns): ?array {
                [$typeColumn, $idColumn] = $columns;

                if (! filled($activity->{$typeColumn}) || ! filled($activity->{$idColumn})) {
                    return null;
                }

                return [
                    'type' => (string) $activity->{$typeColumn},
                    'id' => (int) $activity->{$idColumn},
                ];
            }))
            ->filter()
            ->unique(fn (array $reference) => $this->entityKey($reference['type'], $reference['id']))
            ->values();

        return $references
            ->groupBy('type')
            ->flatMap(function (Collection $items, string $modelClass): array {
                if (! is_a($modelClass, Model::class, true)) {
                    return [];
                }

                $ids = $items->pluck('id')->unique()->values();
                if ($ids->isEmpty()) {
                    return [];
                }

                $models = $modelClass::query()
                    ->select($this->labelColumnsFor($modelClass))
                    ->when($this->usesSoftDeletes($modelClass), fn (Builder $query) => $query->withTrashed())
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                return $ids
                    ->mapWithKeys(function (int $id) use ($modelClass, $models): array {
                        $model = $models->get($id);

                        return [
                            $this->entityKey($modelClass, $id) => $model
                                ? $this->modelLabel($model)
                                : class_basename($modelClass).' #'.$id,
                        ];
                    })
                    ->all();
            })
            ->all();
    }

    private function modelLabel(Model $model): string
    {
        if ($model instanceof User) {
            return trim($model->name) !== ''
                ? $model->name
                : (string) $model->email;
        }

        if ($model instanceof Personnel) {
            return trim($model->fullname) !== ''
                ? $model->fullname
                : (string) $model->tabel_no;
        }

        if ($model instanceof Candidate) {
            return trim($model->fullname) !== ''
                ? $model->fullname
                : class_basename($model).' #'.$model->getKey();
        }

        if ($model instanceof StaffSchedule) {
            return __('audit::activity.labels.staff_schedule', ['id' => $model->getKey()]);
        }

        if ($model instanceof OrderLog) {
            return filled($model->order_no)
                ? __('audit::activity.labels.order_log_with_number', ['number' => $model->order_no])
                : __('audit::activity.labels.order_log', ['id' => $model->getKey()]);
        }

        if ($model instanceof AttendanceOvertimeRequest) {
            return $model->date
                ? __('audit::activity.labels.attendance_overtime_request_with_date', ['date' => $model->date->format('d.m.Y')])
                : __('audit::activity.labels.attendance_overtime_request', ['id' => $model->getKey()]);
        }

        foreach (['name', 'title', 'label'] as $attribute) {
            if (filled($model->{$attribute} ?? null)) {
                return (string) $model->{$attribute};
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    /**
     * @return array<int,string>
     */
    private function labelColumnsFor(string $modelClass): array
    {
        if ($modelClass === User::class) {
            return ['id', 'name', 'email'];
        }

        if ($modelClass === Personnel::class) {
            return ['id', 'surname', 'name', 'patronymic', 'tabel_no'];
        }

        if ($modelClass === Candidate::class) {
            return ['id', 'surname', 'name', 'patronymic'];
        }

        if ($modelClass === StaffSchedule::class) {
            return ['id'];
        }

        if ($modelClass === OrderLog::class) {
            return ['id', 'order_no'];
        }

        if ($modelClass === AttendanceOvertimeRequest::class) {
            return ['id', 'date', 'tabel_no', 'status'];
        }

        return ['id'];
    }

    private function entityKey(?string $type, mixed $id): string
    {
        return ((string) $type).'#'.((string) $id);
    }

    private function translationKey(string $value): string
    {
        return Str::of($value)
            ->replace(['.', '-'], '_')
            ->snake()
            ->toString();
    }

    private function usesSoftDeletes(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }

    private function translateOr(string $key, string $fallback): string
    {
        $translation = __($key);

        return $translation === $key ? $fallback : $translation;
    }
}
