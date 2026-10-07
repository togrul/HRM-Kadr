<?php

namespace App\Modules\Audit\Livewire;

use App\Models\AuditActivity;
use App\Modules\Audit\Application\Services\ActivityLogReader;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogDashboard extends Component
{
    use WithPagination;

    public string $search = '';

    public string $logName = '';

    /** Nullable only so the select's cleared state (null) can land; normalised to ''. */
    public ?string $event = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 25;

    /** Only entries a user caused (the "users" metric card), not system ones. */
    public bool $usersOnly = false;

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
        if (in_array($property, ['search', 'logName', 'event', 'dateFrom', 'dateTo', 'perPage', 'usersOnly'], true)) {
            $this->resetPage();
            $this->selectedActivityId = null;
        }
    }

    public function updatedEvent(): void
    {
        $this->event ??= '';
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->logName = '';
        $this->event = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->perPage = 25;
        $this->usersOnly = false;
        $this->selectedActivityId = null;
        $this->resetPage();
    }

    /**
     * The header metric cards double as filters: a click applies the card's filter, a
     * second click on the active card clears it. "total" clears every card filter.
     */
    public function toggleMetric(string $metric): void
    {
        $active = $this->metricActive($metric);

        if ($metric === 'total') {
            $this->dateFrom = $this->dateTo = $this->event = '';
            $this->usersOnly = false;
        } elseif ($metric === 'today') {
            $this->dateFrom = $this->dateTo = $active ? '' : today()->toDateString();
        } elseif ($metric === 'profile_opened') {
            $this->event = $active ? '' : 'profile_opened';
        } elseif ($metric === 'users') {
            $this->usersOnly = ! $active;
        }

        $this->selectedActivityId = null;
        $this->resetPage();
    }

    public function metricActive(string $metric): bool
    {
        $today = today()->toDateString();

        return match ($metric) {
            'total' => ! $this->metricActive('today') && ! $this->metricActive('profile_opened') && ! $this->usersOnly,
            'today' => $this->dateFrom === $today && $this->dateTo === $today,
            'profile_opened' => $this->event === 'profile_opened',
            'users' => $this->usersOnly,
            default => false,
        };
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
            'users_only' => $this->usersOnly ? '1' : '',
        ], fn ($value) => $value !== ''));
    }

    public function render(): View
    {
        $activities = $this->reader()->query($this->filters())
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
            'summary' => $this->reader()->summary($this->filters()),
            'logNameOptions' => $this->logNameOptions(),
            'eventCounts' => $this->reader()->eventCounts($this->filters()),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function filters(): array
    {
        return [
            'search' => $this->search,
            'log_name' => $this->logName,
            'event' => $this->event,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'users_only' => $this->usersOnly,
        ];
    }

    private function reader(): ActivityLogReader
    {
        return app(ActivityLogReader::class);
    }

    private function selectedActivity(): ?AuditActivity
    {
        if ($this->selectedActivityId === null) {
            return null;
        }

        return AuditActivity::query()->find($this->selectedActivityId);
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

    public function actorLabel(AuditActivity $activity): string
    {
        return $this->reader()->actorLabel($activity, $this->actorLabels);
    }

    public function subjectLabel(AuditActivity $activity): string
    {
        return $this->reader()->subjectLabel($activity, $this->subjectLabels);
    }

    public function eventLabel(?string $event): string
    {
        return $this->reader()->eventLabel($event);
    }

    public function descriptionLabel(?string $description): string
    {
        return $this->reader()->descriptionLabel($description);
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
                'key' => $this->reader()->translateOr(
                    "audit::activity.properties.{$this->reader()->translationKey((string) $key)}",
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
        $reader = $this->reader();
        $labels = $reader->labelsFor($activities);
        $keys = fn (string $type, string $id): array => $activities
            ->filter(fn (AuditActivity $activity) => filled($activity->{$type}) && filled($activity->{$id}))
            ->mapWithKeys(fn (AuditActivity $activity) => [$reader->entityKey($activity->{$type}, $activity->{$id}) => true])
            ->all();

        $this->actorLabels = array_intersect_key($labels, $keys('causer_type', 'causer_id'));
        $this->subjectLabels = array_intersect_key($labels, $keys('subject_type', 'subject_id'));
    }
}
