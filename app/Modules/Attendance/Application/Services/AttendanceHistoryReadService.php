<?php

namespace App\Modules\Attendance\Application\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AttendanceHistoryReadService
{
    /**
     * @var array<string,array<int,string>>
     */
    private const TYPE_EVENT_MAP = [
        'calendar' => ['calendar.%'],
        'shift' => ['shift.%'],
        'assignment' => ['shift_assignment.%'],
        'settings' => ['settings.%'],
        'manual' => ['manual_entry.%'],
        'overtime' => ['overtime.%'],
        'exceptions' => ['exception.%'],
        'month' => ['month.%', 'month_lock.%'],
    ];

    public function paginateRows(
        string $type,
        string $search,
        string $dateFrom,
        string $dateTo,
        int $perPage,
        ?int $subjectId = null
    ): LengthAwarePaginator {
        return $this->baseQuery($type, $search, $dateFrom, $dateTo, $subjectId)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return array<string,int>
     */
    public function totals(
        string $type,
        string $search,
        string $dateFrom,
        string $dateTo,
        ?int $subjectId = null
    ): array {
        $base = $this->baseQuery($type, $search, $dateFrom, $dateTo, $subjectId);

        $aggregate = DB::query()
            ->fromSub((clone $base)->toBase(), 'attendance_history')
            ->selectRaw('COUNT(*) as total_changes')
            ->selectRaw('COUNT(DISTINCT causer_id) as causer_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN event LIKE "calendar.%" THEN 1 ELSE 0 END), 0) as calendar_changes')
            ->selectRaw('COALESCE(SUM(CASE WHEN event LIKE "shift.%" OR event LIKE "shift_assignment.%" THEN 1 ELSE 0 END), 0) as shift_changes')
            ->selectRaw('COALESCE(SUM(CASE WHEN event LIKE "settings.%" THEN 1 ELSE 0 END), 0) as settings_changes')
            ->first();

        return [
            'total_changes' => (int) ($aggregate?->total_changes ?? 0),
            'causer_count' => (int) ($aggregate?->causer_count ?? 0),
            'calendar_changes' => (int) ($aggregate?->calendar_changes ?? 0),
            'shift_changes' => (int) ($aggregate?->shift_changes ?? 0),
            'settings_changes' => (int) ($aggregate?->settings_changes ?? 0),
        ];
    }

    /**
     * @return array<string,string>
     */
    public function typeOptions(): array
    {
        return [
            'all' => __('attendance::history.types.all'),
            'calendar' => __('attendance::history.types.calendar'),
            'shift' => __('attendance::history.types.shift'),
            'assignment' => __('attendance::history.types.assignment'),
            'settings' => __('attendance::history.types.settings'),
            'manual' => __('attendance::history.types.manual'),
            'overtime' => __('attendance::history.types.overtime'),
            'exceptions' => __('attendance::history.types.exceptions'),
            'month' => __('attendance::history.types.month'),
        ];
    }

    public function resolveType(Activity $activity): string
    {
        $event = (string) $activity->event;

        foreach (self::TYPE_EVENT_MAP as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (str($event)->is($pattern)) {
                    return $type;
                }
            }
        }

        return 'all';
    }

    public function subjectLabel(Activity $activity): string
    {
        $before = $this->payload($activity, 'before');
        $after = $this->payload($activity, 'after');

        return match ($this->resolveType($activity)) {
            'calendar' => $this->firstFilled([
                $after['date'] ?? null,
                $before['date'] ?? null,
                $after['name'] ?? null,
                $before['name'] ?? null,
            ], $this->fallbackSubjectLabel(__('attendance::history.labels.calendar_record'), $activity)),
            'shift' => $this->firstFilled([
                $after['name'] ?? null,
                $before['name'] ?? null,
            ], $this->fallbackSubjectLabel(__('attendance::history.labels.shift_record'), $activity)),
            'assignment' => trim(collect([
                $after['tabel_no'] ?? $before['tabel_no'] ?? null,
                ($after['shift_id'] ?? $before['shift_id'] ?? null) !== null
                    ? '#'.($after['shift_id'] ?? $before['shift_id'])
                    : null,
            ])->filter()->implode(' • ')) ?: $this->fallbackSubjectLabel(__('attendance::history.labels.assignment_record'), $activity),
            'settings' => __('attendance::history.labels.settings_record'),
            default => $this->firstFilled([
                $after['tabel_no'] ?? null,
                $before['tabel_no'] ?? null,
            ], $this->fallbackSubjectLabel(__('attendance::history.labels.generic_record'), $activity)),
        };
    }

    public function eventLabel(Activity $activity): string
    {
        $labels = trans('attendance::history.events');

        $eventKey = str_replace('.', '_', (string) $activity->event);

        if (is_array($labels) && array_key_exists($eventKey, $labels)) {
            return (string) $labels[$eventKey];
        }

        return str((string) $activity->event)
            ->replace(['.', '_'], ' ')
            ->headline()
            ->toString();
    }

    /**
     * @return array<int,string>
     */
    public function changedKeys(Activity $activity): array
    {
        $before = $this->payload($activity, 'before');
        $after = $this->payload($activity, 'after');
        $keys = collect(array_unique(array_merge(array_keys($before), array_keys($after))));

        return $keys
            ->filter(function (string $key) use ($before, $after): bool {
                return Arr::get($before, $key) !== Arr::get($after, $key);
            })
            ->map(fn (string $key) => $this->fieldLabel($key))
            ->values()
            ->all();
    }

    /**
     * @return array<string,string>
     */
    public function normalizedPayload(Activity $activity, string $direction): array
    {
        return collect($this->payload($activity, $direction))
            ->mapWithKeys(fn ($value, $key) => [
                $this->fieldLabel((string) $key) => $this->normalizeValue($value, (string) $key),
            ])
            ->all();
    }

    private function baseQuery(
        string $type,
        string $search,
        string $dateFrom,
        string $dateTo,
        ?int $subjectId = null
    ) {
        return Activity::query()
            ->where('log_name', 'attendance')
            ->with('causer:id,name,email')
            ->when($type !== 'all', function ($query) use ($type): void {
                $patterns = self::TYPE_EVENT_MAP[$type] ?? [];
                $query->where(function ($inner) use ($patterns): void {
                    foreach ($patterns as $index => $pattern) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $inner->{$method}('event', 'like', str_replace('*', '%', $pattern));
                    }
                });
            })
            ->when($subjectId !== null, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($search !== '', function ($query) use ($search): void {
                $wildcard = '%'.$search.'%';
                $query->where(function ($inner) use ($wildcard): void {
                    $inner->where('event', 'like', $wildcard)
                        ->orWhere('description', 'like', $wildcard)
                        ->orWhereHas('causer', function ($causerQuery) use ($wildcard): void {
                            $causerQuery->where('name', 'like', $wildcard)
                                ->orWhere('email', 'like', $wildcard);
                        });
                });
            });
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(Activity $activity, string $direction): array
    {
        $properties = $activity->properties instanceof Collection
            ? $activity->properties->toArray()
            : (array) $activity->properties;

        $payload = data_get($properties, $direction, []);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<int,mixed>  $values
     */
    private function firstFilled(array $values, string $fallback): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return $this->normalizeDisplayString((string) $value);
            }
        }

        return $fallback;
    }

    private function fallbackSubjectLabel(string $label, Activity $activity): string
    {
        return $activity->subject_id !== null
            ? sprintf('%s #%s', $label, $activity->subject_id)
            : $label;
    }

    private function fieldLabel(string $key): string
    {
        $labels = trans('attendance::history.fields');

        if (is_array($labels) && array_key_exists($key, $labels)) {
            return (string) $labels[$key];
        }

        return str($key)->replace('_', ' ')->headline()->toString();
    }

    private function normalizeValue(mixed $value, ?string $key = null): string
    {
        if (is_bool($value)) {
            return $value ? __('attendance::history.labels.yes') : __('attendance::history.labels.no');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_string($value) && str_starts_with($value, 'attendance::')) {
            $translated = __($value);

            return $translated === $value ? $value : $translated;
        }

        if ($key !== null) {
            $fieldValueLabels = trans('attendance::history.field_values');

            if (is_array($fieldValueLabels) && isset($fieldValueLabels[$key]) && is_array($fieldValueLabels[$key])) {
                $normalized = (string) $value;

                if (array_key_exists($normalized, $fieldValueLabels[$key])) {
                    return (string) $fieldValueLabels[$key][$normalized];
                }
            }
        }

        return $this->normalizeDisplayString((string) $value);
    }

    private function normalizeDisplayString(string $value): string
    {
        if (str_starts_with($value, 'attendance::')) {
            $translated = __($value);

            return $translated === $value ? $value : $translated;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return Carbon::parse($value)->format('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $value) === 1) {
            return Carbon::parse($value)->format('Y-m-d H:i');
        }

        return $value;
    }
}
