<?php

namespace App\Modules\Audit\Application\Services;

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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One reading of the audit log for the dashboard and its export: the same filters, the
 * same header figures and the same human labels (names instead of "User #5", localized
 * events instead of raw codes).
 *
 * Filters: search, log_name, event (self::NO_EVENT = entries without an event), date_from,
 * date_to, users_only.
 */
class ActivityLogReader
{
    public const NO_EVENT = '__none__';

    /** Cap on name matches a search expands into, so a one-letter term stays cheap. */
    private const NAME_MATCH_LIMIT = 500;

    /**
     * @param  array<string,mixed>  $filters
     */
    public function query(array $filters, bool $ignoreEvent = false): Builder
    {
        $query = $this->baseQuery($filters);
        $event = $this->filter($filters, 'event');

        return $query
            ->when(! $ignoreEvent && $event !== '', fn (Builder $query) => $this->whereEvent($query, $event))
            ->when($this->usersOnly($filters), fn (Builder $query) => $query->whereNotNull('causer_id'))
            // Plain ranges, not whereDate(): DATE(created_at) cannot use the index.
            ->when($this->dayStart($this->filter($filters, 'date_from')), fn (Builder $query, Carbon $from) => $query->where('created_at', '>=', $from))
            ->when($this->dayStart($this->filter($filters, 'date_to')), fn (Builder $query, Carbon $to) => $query->where('created_at', '<', $to->addDay()));
    }

    /**
     * The header cards. Each figure is what clicking that card lists: the card's own
     * condition plus every other filter the click keeps ("total" clears the card filters,
     * so it only keeps search and log type). One scan, conditional aggregates.
     *
     * @param  array<string,mixed>  $filters
     * @return array{total:int,today:int,profile_opened:int,users:int}
     */
    public function summary(array $filters): array
    {
        $today = today();
        $date = [];
        $from = $this->dayStart($this->filter($filters, 'date_from'));
        $to = $this->dayStart($this->filter($filters, 'date_to'));

        if ($from) {
            $date[] = ['created_at >= ?', [$from->toDateTimeString()]];
        }

        if ($to) {
            $date[] = ['created_at < ?', [$to->addDay()->toDateTimeString()]];
        }

        $event = $this->filter($filters, 'event');
        $eventCondition = match ($event) {
            '' => [],
            self::NO_EVENT => [["(event is null or event = '')", []]],
            default => [['event = ?', [$event]]],
        };
        $users = $this->usersOnly($filters) ? [['causer_id is not null', []]] : [];

        $sums = [
            'today_count' => [...$eventCondition, ...$users, ['created_at >= ?', [$today->toDateTimeString()]], ['created_at < ?', [$today->copy()->addDay()->toDateTimeString()]]],
            'profile_opened_count' => [...$date, ...$users, ['event = ?', ['profile_opened']]],
            'user_count' => [...$date, ...$eventCondition, ['causer_id is not null', []]],
        ];

        $query = $this->baseQuery($filters)->selectRaw('count(*) as total_count');

        foreach ($sums as $alias => $conditions) {
            $query->selectRaw(
                'sum(case when '.implode(' and ', array_column($conditions, 0)).' then 1 else 0 end) as '.$alias,
                array_merge(...array_column($conditions, 1))
            );
        }

        $row = $query->toBase()->first();

        return [
            'total' => (int) ($row->total_count ?? 0),
            'today' => (int) ($row->today_count ?? 0),
            'profile_opened' => (int) ($row->profile_opened_count ?? 0),
            'users' => (int) ($row->user_count ?? 0),
        ];
    }

    /**
     * Entries per event inside the rest of the filter. Rows without an event are keyed
     * self::NO_EVENT, so the rows always add up to "Hamısı".
     *
     * @param  array<string,mixed>  $filters
     * @return Collection<string,int>
     */
    public function eventCounts(array $filters): Collection
    {
        return $this->query($filters, ignoreEvent: true)
            ->select('event')
            ->selectRaw('count(*) as event_count')
            ->groupBy('event')
            ->orderBy('event')
            ->toBase()
            ->get()
            ->reduce(function (Collection $counts, object $row): Collection {
                $key = filled($row->event) ? (string) $row->event : self::NO_EVENT;

                return $counts->put($key, $counts->get($key, 0) + (int) $row->event_count);
            }, collect());
    }

    /**
     * Readable names for every causer and subject of the given entries, keyed by
     * entityKey(). One query per model type.
     *
     * @return array<string,string>
     */
    public function labelsFor(Collection $activities): array
    {
        $references = collect([['causer_type', 'causer_id'], ['subject_type', 'subject_id']])
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

                // Only the id to read means the label is the "Class #id" fallback anyway.
                if ($this->labelColumnsFor($modelClass) === ['id'] && $modelClass !== StaffSchedule::class) {
                    return $ids->mapWithKeys(fn (int $id): array => [$this->entityKey($modelClass, $id) => class_basename($modelClass).' #'.$id])->all();
                }

                $models = $modelClass::query()
                    ->select($this->labelColumnsFor($modelClass))
                    ->when($this->usesSoftDeletes($modelClass), fn (Builder $query) => $query->withTrashed())
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                return $ids
                    ->mapWithKeys(fn (int $id): array => [
                        $this->entityKey($modelClass, $id) => $models->has($id)
                            ? $this->modelLabel($models->get($id))
                            : class_basename($modelClass).' #'.$id,
                    ])
                    ->all();
            })
            ->all();
    }

    /**
     * @param  array<string,string>  $labels
     */
    public function actorLabel(AuditActivity $activity, array $labels): string
    {
        if ($activity->causer_id === null) {
            return __('audit::activity.labels.system_actor');
        }

        return $labels[$this->entityKey($activity->causer_type, $activity->causer_id)]
            ?? class_basename((string) $activity->causer_type).' #'.$activity->causer_id;
    }

    /**
     * @param  array<string,string>  $labels
     */
    public function subjectLabel(AuditActivity $activity, array $labels): string
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

        return $labels[$this->entityKey($activity->subject_type, $activity->subject_id)]
            ?? class_basename((string) $activity->subject_type).' #'.$activity->subject_id;
    }

    public function eventLabel(?string $event): string
    {
        if (! $event || $event === self::NO_EVENT) {
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

    public function entityKey(?string $type, mixed $id): string
    {
        return ((string) $type).'#'.((string) $id);
    }

    public function translationKey(string $value): string
    {
        return Str::of($value)
            ->replace(['.', '-'], '_')
            ->snake()
            ->toString();
    }

    public function translateOr(string $key, string $fallback): string
    {
        $translation = __($key);

        return $translation === $key ? $fallback : $translation;
    }

    /**
     * Search and log type: the filters every header card keeps.
     *
     * @param  array<string,mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $logName = $this->filter($filters, 'log_name');
        $search = $this->filter($filters, 'search');

        return AuditActivity::query()
            ->when($logName !== '', fn (Builder $query) => $query->where('log_name', $logName))
            ->when($search !== '', fn (Builder $query) => $this->whereSearch($query, $search));
    }

    private function whereSearch(Builder $query, string $search): void
    {
        $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        // The audit log may live on its own connection, so name matches are resolved
        // first and joined in by id rather than through a cross-database subquery.
        $userIds = User::query()
            ->where(fn (Builder $users) => $users->where('name', 'like', $term)->orWhere('email', 'like', $term))
            ->limit(self::NAME_MATCH_LIMIT)
            ->pluck('id');

        $personnelIds = Personnel::query()
            ->where(function (Builder $personnel) use ($search): void {
                foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                    $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $word).'%';
                    $personnel->where(fn (Builder $name) => $name
                        ->where('surname', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('patronymic', 'like', $like)
                        ->orWhere('tabel_no', 'like', $like));
                }
            })
            ->limit(self::NAME_MATCH_LIMIT)
            ->pluck('id');

        $query->where(function (Builder $nested) use ($term, $userIds, $personnelIds): void {
            $nested
                ->where('description', 'like', $term)
                ->orWhere('event', 'like', $term)
                ->orWhere('log_name', 'like', $term)
                ->orWhere('subject_type', 'like', $term)
                ->orWhere('causer_type', 'like', $term);

            if ($userIds->isNotEmpty()) {
                $nested->orWhere(fn (Builder $causer) => $causer->where('causer_type', User::class)->whereIn('causer_id', $userIds));
            }

            if ($personnelIds->isNotEmpty()) {
                $nested->orWhere(fn (Builder $subject) => $subject->where('subject_type', Personnel::class)->whereIn('subject_id', $personnelIds));
            }
        });
    }

    private function whereEvent(Builder $query, string $event): Builder
    {
        if ($event === self::NO_EVENT) {
            return $query->where(fn (Builder $none) => $none->whereNull('event')->orWhere('event', ''));
        }

        return $query->where('event', $event);
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    private function usersOnly(array $filters): bool
    {
        return in_array($filters['users_only'] ?? false, [true, 1, '1'], true);
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    private function filter(array $filters, string $key): string
    {
        return trim((string) ($filters[$key] ?? ''));
    }

    private function dayStart(string $date): ?Carbon
    {
        return $date === '' ? null : rescue(fn (): Carbon => Carbon::parse($date)->startOfDay(), null, false);
    }

    private function modelLabel(Model $model): string
    {
        if ($model instanceof User) {
            return trim((string) $model->name) !== '' ? $model->name : (string) $model->email;
        }

        if ($model instanceof Personnel) {
            return trim($model->fullname) !== '' ? $model->fullname : (string) $model->tabel_no;
        }

        if ($model instanceof Candidate) {
            return trim($model->fullname) !== '' ? $model->fullname : class_basename($model).' #'.$model->getKey();
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
        return match ($modelClass) {
            User::class => ['id', 'name', 'email'],
            Personnel::class => ['id', 'surname', 'name', 'patronymic', 'tabel_no'],
            Candidate::class => ['id', 'surname', 'name', 'patronymic'],
            OrderLog::class => ['id', 'order_no'],
            AttendanceOvertimeRequest::class => ['id', 'date', 'tabel_no', 'status'],
            default => ['id'],
        };
    }

    private function usesSoftDeletes(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }
}
