<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\User;
use App\Modules\Reports\Domain\Models\ReportSchedule;
use App\Services\StructureService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Planlı hesabatların idarəsi: yarat, dəyiş, aktiv/passiv et, sil. Seçim sahibin hesabat qrupu
 * və struktur görünürlüyünə qarşı yoxlanılır; alıcılar yalnız aktiv sistem istifadəçiləridir
 * (sərbəst email ünvanı qəbul olunmur) və hesabatın qrupuna baxış icazəsi olmalıdır.
 */
class ReportScheduleService
{
    public const NAME_MAX = 120;

    public function __construct(
        private ReportSelectionService $selections,
        private ReportsAccessService $access,
        private ReportsAuditLogger $audit,
        private StructureService $structures,
    ) {}

    /**
     * @return Collection<int,ReportSchedule>
     */
    public function listFor(User $owner): Collection
    {
        if (! Gate::forUser($owner)->allows('viewAny', ReportSchedule::class)) {
            return collect();
        }

        return ReportSchedule::query()
            ->where('user_id', $owner->getKey())
            ->withCount('recipients')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string,mixed>  $data  name, tab, filters, period, frequency, weekday, month_day,
     *                                     send_time, format, recipient_ids, is_active
     *
     * @throws ValidationException
     */
    public function save(User $owner, array $data, ?ReportSchedule $schedule = null): ReportSchedule
    {
        $gate = Gate::forUser($owner);
        $schedule === null
            ? $gate->authorize('create', ReportSchedule::class)
            : $gate->authorize('update', $schedule);

        $attributes = $this->validated($owner, $data);
        $recipientIds = $this->validatedRecipients($owner, $attributes['tab'], $attributes['filters'], $data['recipient_ids'] ?? []);

        $schedule = DB::transaction(function () use ($owner, $attributes, $recipientIds, $schedule): ReportSchedule {
            $model = $schedule ?? new ReportSchedule(['user_id' => $owner->getKey()]);
            $model->fill($attributes);
            $model->next_run_at = $model->is_active ? $model->nextRunAfter(now()) : null;
            $model->save();
            $model->recipients()->sync($recipientIds);

            return $model;
        });

        $this->audit->log(
            $schedule->wasRecentlyCreated ? ReportsAuditLogger::EVENT_SCHEDULE_CREATED : ReportsAuditLogger::EVENT_SCHEDULE_UPDATED,
            'Report schedule saved',
            $this->auditProperties($schedule) + ['recipient_ids' => $recipientIds],
            $schedule,
            $owner,
        );

        return $schedule;
    }

    public function setActive(User $owner, ReportSchedule $schedule, bool $active): ReportSchedule
    {
        Gate::forUser($owner)->authorize('update', $schedule);

        $schedule->is_active = $active;
        $schedule->next_run_at = $active ? $schedule->nextRunAfter(now()) : null;
        $schedule->save();

        $this->audit->log(ReportsAuditLogger::EVENT_SCHEDULE_UPDATED, 'Report schedule toggled', $this->auditProperties($schedule), $schedule, $owner);

        return $schedule;
    }

    public function delete(User $owner, ReportSchedule $schedule): void
    {
        Gate::forUser($owner)->authorize('delete', $schedule);

        $properties = $this->auditProperties($schedule);
        $schedule->delete();

        $this->audit->log(ReportsAuditLogger::EVENT_SCHEDULE_DELETED, 'Report schedule deleted', $properties, null, $owner);
    }

    /**
     * Alıcı ola biləcək istifadəçilər: aktiv, email ünvanı olan, hesabat qrupuna baxa bilən
     * və faylda olacaq strukturların HAMISINI özü də görən. Fayl sahibin görünürlüyü ilə
     * qurulur; alıcı sahibdən dar görünürlüyə malikdirsə, email ona öz strukturundan
     * kənar məlumatı daşıyardı — belə alıcı qəbul olunmur.
     *
     * @param  array<string,mixed>  $selection
     * @param  list<int>  $ids
     * @return Collection<int,User>
     */
    public function eligibleRecipients(string $tab, array $selection, array $ids, User $owner): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get()
            ->filter(fn (User $user): bool => $this->selections->isViewable($user, $tab, $selection)
                && $this->coversOwnerScope($user, $owner, $selection))
            ->values();
    }

    /**
     * Seçilmiş struktur varsa alıcı onu görməlidir; yoxdursa alıcının görünürlüyü sahibin
     * görünürlüyünü tam əhatə etməlidir («bütün strukturlar» bayrağı hər şeyi əhatə edir).
     *
     * @param  array<string,mixed>  $selection
     */
    private function coversOwnerScope(User $recipient, User $owner, array $selection): bool
    {
        if ((int) $recipient->getKey() === (int) $owner->getKey() || $this->structures->hasAllStructures($recipient)) {
            return true;
        }

        $structureId = (int) ($selection['structure_id'] ?? 0);
        $recipientScope = $this->structures->scopeFor($recipient);

        if ($structureId > 0) {
            return $recipientScope->allows($structureId);
        }

        if ($this->structures->hasAllStructures($owner)) {
            return false;
        }

        foreach ($this->structures->getAccessibleStructures($owner) as $id) {
            if (! $recipientScope->allows($id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Alıcı axtarışı (UI): ad və ya email üzrə, yalnız hesabat istifadəçiləri.
     *
     * @return list<array{id:int,name:string,email:string}>
     */
    public function recipientCandidates(string $search, int $limit = 10): array
    {
        $term = trim($search);
        if (mb_strlen($term) < 2) {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->limit($limit * 3)
            ->get(['id', 'name', 'email'])
            ->filter(fn (User $user): bool => $this->access->canView($user))
            ->take($limit)
            ->map(fn (User $user): array => ['id' => (int) $user->id, 'name' => (string) $user->name, 'email' => (string) $user->email])
            ->values()
            ->all();
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     *
     * @throws ValidationException
     */
    private function validated(User $owner, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > self::NAME_MAX) {
            throw ValidationException::withMessages(['name' => __('reports::schedules.errors.invalid_name', ['max' => self::NAME_MAX])]);
        }

        $tab = (string) ($data['tab'] ?? '');
        $filters = is_array($data['filters'] ?? null) ? $data['filters'] : [];
        $selection = $this->selections->validated($owner, $tab, $filters, withPeriod: false);

        $period = (string) ($data['period'] ?? ReportSchedule::PERIOD_CURRENT_MONTH);
        $frequency = (string) ($data['frequency'] ?? '');
        $format = (string) ($data['format'] ?? '');
        $sendTime = (string) ($data['send_time'] ?? '');

        if (! in_array($period, ReportSchedule::PERIODS, true)) {
            throw ValidationException::withMessages(['period' => __('reports::schedules.errors.invalid_period')]);
        }

        if (! in_array($frequency, ReportSchedule::FREQUENCIES, true)) {
            throw ValidationException::withMessages(['frequency' => __('reports::schedules.errors.invalid_frequency')]);
        }

        if (! in_array($format, ReportSchedule::FORMATS, true)) {
            throw ValidationException::withMessages(['format' => __('reports::schedules.errors.invalid_format')]);
        }

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $sendTime) !== 1) {
            throw ValidationException::withMessages(['send_time' => __('reports::schedules.errors.invalid_time')]);
        }

        $weekday = null;
        $monthDay = null;

        if ($frequency === ReportSchedule::FREQUENCY_WEEKLY) {
            $weekday = (int) ($data['weekday'] ?? 0);
            if ($weekday < 1 || $weekday > 7) {
                throw ValidationException::withMessages(['weekday' => __('reports::schedules.errors.invalid_weekday')]);
            }
        }

        if ($frequency === ReportSchedule::FREQUENCY_MONTHLY) {
            $monthDay = (int) ($data['month_day'] ?? 0);
            if ($monthDay < 1 || $monthDay > 28) {
                throw ValidationException::withMessages(['month_day' => __('reports::schedules.errors.invalid_month_day')]);
            }
        }

        return [
            'name' => $name,
            'tab' => $tab,
            'filters' => $selection,
            'period' => $period,
            'frequency' => $frequency,
            'weekday' => $weekday,
            'month_day' => $monthDay,
            'send_time' => $sendTime,
            'format' => $format,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    /**
     * @param  array<string,mixed>  $selection
     * @return list<int>
     *
     * @throws ValidationException
     */
    private function validatedRecipients(User $owner, string $tab, array $selection, mixed $ids): array
    {
        $ids = collect(is_array($ids) ? $ids : [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $max = max(1, (int) config('reports.schedules.max_recipients', 20));

        if ($ids === []) {
            throw ValidationException::withMessages(['recipient_ids' => __('reports::schedules.errors.no_recipients')]);
        }

        if (count($ids) > $max) {
            throw ValidationException::withMessages(['recipient_ids' => __('reports::schedules.errors.too_many_recipients', ['max' => $max])]);
        }

        $eligible = $this->eligibleRecipients($tab, $selection, $ids, $owner)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if (count($eligible) !== count($ids)) {
            throw ValidationException::withMessages(['recipient_ids' => __('reports::schedules.errors.recipient_not_allowed')]);
        }

        return $eligible;
    }

    /**
     * @return array<string,mixed>
     */
    private function auditProperties(ReportSchedule $schedule): array
    {
        return [
            'schedule_id' => $schedule->id,
            'schedule_name' => $schedule->name,
            'owner_id' => $schedule->user_id,
            'tab' => $schedule->tab,
            'filters' => $schedule->filters,
            'frequency' => $schedule->frequency,
            'format' => $schedule->format,
            'active' => $schedule->is_active,
        ];
    }
}
