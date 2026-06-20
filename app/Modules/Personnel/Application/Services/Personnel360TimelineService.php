<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\AuditActivity;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Personnel360TimelineService
{
    /**
     * Small per-build lookup cache so repeated audit rows do not re-query the
     * same reference values such as structures, countries and positions.
     */
    private array $lookupCache = [];

    public function build(Personnel $personnel, ?string $search = null, int $limit = 80, array $filters = []): Collection
    {
        $search = filled($search) ? mb_strtolower(trim((string) $search)) : null;
        $type = filled($filters['type'] ?? null) ? (string) $filters['type'] : null;
        $dateFrom = filled($filters['date_from'] ?? null) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $dateTo = filled($filters['date_to'] ?? null) ? Carbon::parse($filters['date_to'])->endOfDay() : null;

        return collect()
            ->concat($this->orders($personnel))
            ->concat($this->leaves($personnel))
            ->concat($this->vacations($personnel))
            ->concat($this->businessTrips($personnel))
            ->concat($this->trainingNeeds($personnel))
            ->concat($this->trainingDeliveries($personnel))
            ->concat($this->performanceForms($personnel))
            ->concat($this->lifecycleEvents($personnel))
            ->concat($this->auditChanges($personnel))
            ->concat(app(ProfessionalPortfolioTimelineService::class)->build($personnel))
            ->when($search, fn (Collection $items) => $items->filter(fn (array $item): bool => $this->matchesSearch($item, $search)))
            ->when($type, fn (Collection $items) => $items->filter(fn (array $item): bool => ($item['type'] ?? null) === $type))
            ->when($dateFrom, fn (Collection $items) => $items->filter(fn (array $item): bool => $this->itemDate($item)?->gte($dateFrom) ?? false))
            ->when($dateTo, fn (Collection $items) => $items->filter(fn (array $item): bool => $this->itemDate($item)?->lte($dateTo) ?? false))
            ->sortByDesc(fn (array $item) => ($item['sort_at'] ?? $item['occurred_at'] ?? null) ?: '0000-00-00 00:00:00')
            ->take(max(10, $limit))
            ->values();
    }

    private function orders(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('order_logs') || ! Schema::hasTable('order_log_personnels')) {
            return collect();
        }

        return DB::table('order_logs')
            ->join('order_log_personnels', 'order_log_personnels.order_no', '=', 'order_logs.order_no')
            ->where('order_log_personnels.tabel_no', $personnel->tabel_no)
            ->latest('order_logs.given_date')
            ->limit(25)
            ->get([
                'order_logs.id',
                'order_logs.order_no',
                'order_logs.given_date',
                'order_logs.description',
            ])
            ->map(fn ($row): array => $this->item(
                type: 'order',
                occurredAt: $row->given_date,
                title: __('personnel::portfolio.timeline_titles.order', ['number' => $row->order_no ?: $row->id]),
                summary: $this->stringify($row->description),
                status: null,
                recordId: (int) $row->id,
            ));
    }

    private function leaves(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('leaves')) {
            return collect();
        }

        return DB::table('leaves')
            ->where('tabel_no', $personnel->tabel_no)
            ->latest('starts_at')
            ->limit(25)
            ->get(['id', 'starts_at', 'ends_at', 'reason', 'status_id'])
            ->map(fn ($row): array => $this->item(
                type: 'leave',
                occurredAt: $row->starts_at,
                title: __('personnel::portfolio.timeline_titles.leave'),
                summary: trim(implode(' - ', array_filter([
                    $this->dateRange($row->starts_at, $row->ends_at),
                    (string) $row->reason,
                ]))),
                status: $row->status_id ? __('personnel::portfolio.timeline_status.status_id', ['id' => $row->status_id]) : null,
                recordId: (int) $row->id,
            ));
    }

    private function vacations(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('personnel_vacations')) {
            return collect();
        }

        return DB::table('personnel_vacations')
            ->where('tabel_no', $personnel->tabel_no)
            ->latest('start_date')
            ->limit(25)
            ->get(['id', 'start_date', 'end_date', 'vacation_places', 'approval_status', 'order_no'])
            ->map(fn ($row): array => $this->item(
                type: 'vacation',
                occurredAt: $row->start_date,
                title: __('personnel::portfolio.timeline_titles.vacation'),
                summary: trim(implode(' - ', array_filter([
                    $this->dateRange($row->start_date, $row->end_date),
                    (string) $row->vacation_places,
                    $row->order_no ? __('personnel::portfolio.timeline_titles.order', ['number' => $row->order_no]) : null,
                ]))),
                status: $row->approval_status,
                recordId: (int) $row->id,
            ));
    }

    private function businessTrips(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('personnel_business_trips')) {
            return collect();
        }

        return DB::table('personnel_business_trips')
            ->where('tabel_no', $personnel->tabel_no)
            ->latest('start_date')
            ->limit(25)
            ->get(['id', 'start_date', 'end_date', 'location', 'description', 'approval_status', 'order_no'])
            ->map(fn ($row): array => $this->item(
                type: 'business_trip',
                occurredAt: $row->start_date,
                title: __('personnel::portfolio.timeline_titles.business_trip'),
                summary: trim(implode(' - ', array_filter([
                    $this->dateRange($row->start_date, $row->end_date),
                    (string) $row->location,
                    (string) $row->description,
                    $row->order_no ? __('personnel::portfolio.timeline_titles.order', ['number' => $row->order_no]) : null,
                ]))),
                status: $row->approval_status,
                recordId: (int) $row->id,
            ));
    }

    private function trainingNeeds(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('training_need_items')) {
            return collect();
        }

        return DB::table('training_need_items')
            ->leftJoin('training_competencies', 'training_competencies.id', '=', 'training_need_items.training_competency_id')
            ->where('training_need_items.personnel_id', $personnel->id)
            ->latest('training_need_items.created_at')
            ->limit(25)
            ->get([
                'training_need_items.id',
                'training_need_items.created_at',
                'training_need_items.priority',
                'training_need_items.status',
                'training_need_items.reason',
                'training_competencies.name as competency_name',
            ])
            ->map(fn ($row): array => $this->item(
                type: 'training_need',
                occurredAt: $row->created_at,
                title: __('personnel::portfolio.timeline_titles.training_need', ['competency' => $row->competency_name ?: '#'.$row->id]),
                summary: (string) $row->reason,
                status: $row->status ?: $row->priority,
                recordId: (int) $row->id,
            ));
    }

    private function trainingDeliveries(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('training_delivery_records')) {
            return collect();
        }

        return DB::table('training_delivery_records')
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_delivery_records.training_program_id')
            ->where('training_delivery_records.personnel_id', $personnel->id)
            ->latest('training_delivery_records.completed_at')
            ->limit(25)
            ->get([
                'training_delivery_records.id',
                'training_delivery_records.completed_at',
                'training_delivery_records.result_status',
                'training_delivery_records.attended_hours',
                'training_programs.title as program_name',
            ])
            ->map(fn ($row): array => $this->item(
                type: 'training_delivery',
                occurredAt: $row->completed_at,
                title: __('personnel::portfolio.timeline_titles.training_delivery', ['program' => $row->program_name ?: '#'.$row->id]),
                summary: $row->attended_hours ? __('personnel::portfolio.timeline_titles.training_hours', ['hours' => $row->attended_hours]) : null,
                status: $row->result_status,
                recordId: (int) $row->id,
            ));
    }

    private function performanceForms(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('performance_forms')) {
            return collect();
        }

        return DB::table('performance_forms')
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
            ->where('performance_forms.personnel_id', $personnel->id)
            ->latest('performance_forms.updated_at')
            ->limit(25)
            ->get([
                'performance_forms.id',
                'performance_forms.updated_at',
                'performance_forms.final_score',
                'performance_forms.final_category',
                'performance_forms.result_status',
                'performance_cycles.name as cycle_name',
            ])
            ->map(fn ($row): array => $this->item(
                type: 'performance',
                occurredAt: $row->updated_at,
                title: __('personnel::portfolio.timeline_titles.performance', ['cycle' => $row->cycle_name ?: '#'.$row->id]),
                summary: $row->final_score ? __('personnel::portfolio.timeline_titles.performance_score', ['score' => $row->final_score]) : null,
                status: $row->final_category ?: $row->result_status,
                recordId: (int) $row->id,
            ));
    }

    private function lifecycleEvents(Personnel $personnel): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_events')) {
            return collect();
        }

        return DB::table('employee_lifecycle_events')
            ->leftJoin('users', 'users.id', '=', 'employee_lifecycle_events.owner_user_id')
            ->where('employee_lifecycle_events.personnel_id', $personnel->id)
            ->latest('employee_lifecycle_events.effective_date')
            ->latest('employee_lifecycle_events.created_at')
            ->limit(25)
            ->get([
                'employee_lifecycle_events.id',
                'employee_lifecycle_events.type',
                'employee_lifecycle_events.status',
                'employee_lifecycle_events.title',
                'employee_lifecycle_events.description',
                'employee_lifecycle_events.effective_date',
                'employee_lifecycle_events.deadline_at',
                'users.name as owner_name',
            ])
            ->map(fn ($row): array => $this->item(
                type: 'lifecycle',
                occurredAt: $row->effective_date ?: $row->deadline_at,
                title: __('personnel::portfolio.timeline_titles.lifecycle', [
                    'type' => __('employee-lifecycle::dashboard.types.'.($row->type ?: 'onboarding')),
                    'title' => $row->title ?: '#'.$row->id,
                ]),
                summary: trim(implode(' - ', array_filter([
                    $this->stringify($row->description),
                    $row->deadline_at ? __('personnel::portfolio.timeline_titles.lifecycle_deadline', ['date' => $this->date($row->deadline_at)]) : null,
                ]))),
                status: $row->status ? __('employee-lifecycle::dashboard.statuses.'.$row->status) : null,
                recordId: (int) $row->id,
                role: $row->owner_name ? __('personnel::portfolio.timeline_titles.changed_by', ['actor' => $row->owner_name]) : null,
            ));
    }

    private function auditChanges(Personnel $personnel): Collection
    {
        $connection = config('activitylog.database_connection') ?: config('database.default');
        $table = (string) config('activitylog.table_name', 'activity_log');

        if (! Schema::connection($connection)->hasTable($table)) {
            return collect();
        }

        $activities = AuditActivity::query()
            ->where('subject_type', Personnel::class)
            ->where('subject_id', $personnel->id)
            ->whereIn('event', ['created', 'updated', 'deleted', 'restored', 'force_deleted'])
            ->latest('created_at')
            ->limit(25)
            ->get([
                'id',
                'description',
                'event',
                'causer_type',
                'causer_id',
                'properties',
                'created_at',
            ]);

        if ($activities->isEmpty()) {
            return collect();
        }

        $causerLabels = User::query()
            ->whereIn('id', $activities->pluck('causer_id')->filter()->unique())
            ->pluck('name', 'id');

        return $activities->map(fn (AuditActivity $activity): array => $this->item(
            type: 'audit',
            occurredAt: $activity->created_at,
            title: __('personnel::portfolio.timeline_titles.audit_change', [
                'event' => $this->auditEventLabel((string) $activity->event),
            ]),
            summary: $this->changedFieldSummary($activity) ?: $this->stringify($activity->description),
            status: (string) $activity->event,
            recordId: (int) $activity->id,
            role: $activity->causer_id
                ? __('personnel::portfolio.timeline_titles.changed_by', [
                    'actor' => $causerLabels->get($activity->causer_id) ?: __('personnel::portfolio.timeline_titles.unknown_actor'),
                ])
                : __('personnel::portfolio.timeline_titles.system_actor'),
        ));
    }

    private function item(string $type, mixed $occurredAt, string $title, ?string $summary, ?string $status, int $recordId, ?string $role = null): array
    {
        return [
            'type' => $type,
            'occurred_at' => $this->date($occurredAt),
            'sort_at' => $this->sortDate($occurredAt),
            'title' => $title,
            'role' => $role,
            'summary' => $summary,
            'status' => $status,
            'record_id' => $recordId,
        ];
    }

    private function matchesSearch(array $item, string $search): bool
    {
        return str_contains(mb_strtolower(implode(' ', array_filter([
            $item['type'] ?? '',
            $item['title'] ?? '',
            $item['summary'] ?? '',
            $item['status'] ?? '',
            $item['role'] ?? '',
        ]))), $search);
    }

    private function date(mixed $value): ?string
    {
        return filled($value) ? Carbon::parse($value)->toDateString() : null;
    }

    private function sortDate(mixed $value): ?string
    {
        return filled($value) ? Carbon::parse($value)->toDateTimeString() : null;
    }

    private function itemDate(array $item): ?Carbon
    {
        $value = $item['sort_at'] ?? $item['occurred_at'] ?? null;

        return filled($value) ? Carbon::parse($value) : null;
    }

    private function dateRange(mixed $from, mixed $to): string
    {
        return trim(implode(' - ', array_filter([
            $this->date($from),
            $this->date($to),
        ])));
    }

    private function stringify(mixed $value): ?string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded)
                ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $value;
        }

        return filled($value) ? (string) $value : null;
    }

    private function changedFieldSummary(AuditActivity $activity): ?string
    {
        $properties = $activity->properties;
        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }

        if (! is_array($properties)) {
            return null;
        }

        $attributes = (array) data_get($properties, 'attributes', []);
        $old = (array) data_get($properties, 'old', []);

        $changes = collect(array_unique(array_merge(
            array_keys($attributes),
            array_keys($old),
        )))
            ->reject(fn (string $field): bool => in_array($field, ['created_at', 'updated_at', 'deleted_at'], true))
            ->map(function (string $field) use ($attributes, $old): string {
                $oldValue = $this->fieldValueLabel($field, $old[$field] ?? null);
                $newValue = $this->fieldValueLabel($field, $attributes[$field] ?? null);

                return __('personnel::portfolio.timeline_titles.changed_field_pair', [
                    'field' => $this->fieldLabel($field),
                    'old' => $oldValue,
                    'new' => $newValue,
                ]);
            })
            ->values();

        if ($changes->isEmpty()) {
            return null;
        }

        return __('personnel::portfolio.timeline_titles.changed_fields', [
            'fields' => $changes->take(8)->implode('; '),
        ]);
    }

    private function fieldLabel(string $field): string
    {
        $explicit = [
            'tabel_no' => __('personnel::common.labels.tabel_no'),
            'surname' => __('personnel::common.labels.surname'),
            'name' => __('personnel::common.labels.name'),
            'patronymic' => __('personnel::common.labels.patronymic'),
            'previous_surname' => __('personnel::common.labels.previous_surname'),
            'previous_name' => __('personnel::common.labels.previous_name'),
            'previous_patronymic' => __('personnel::common.labels.previous_patronymic'),
            'has_changed_initials' => __('personnel::common.labels.previous_name'),
            'initials_changed_date' => __('personnel::common.labels.change_date'),
            'initials_change_reason' => __('personnel::common.labels.change_reason'),
            'birthdate' => __('personnel::common.labels.birthdate'),
            'gender' => __('personnel::common.labels.gender'),
            'nationality_id' => __('personnel::common.labels.nationality'),
            'previous_nationality_id' => __('personnel::common.labels.previous_nationality'),
            'has_changed_nationality' => __('personnel::common.labels.previous_nationality'),
            'nationality_changed_date' => __('personnel::common.labels.nationality_change_date'),
            'nationality_change_reason' => __('personnel::common.labels.nationality_change_reason'),
            'born_country_id' => __('personnel::common.labels.born_country'),
            'born_city_id' => __('personnel::common.labels.city'),
            'birthplace' => __('personnel::common.labels.birthplace'),
            'family_status' => __('personnel::common.labels.family_status'),
            'is_married' => __('personnel::common.labels.family_status'),
            'pin' => __('personnel::common.labels.pin'),
            'phone' => __('personnel::common.labels.phone'),
            'mobile' => __('personnel::common.labels.mobile'),
            'email' => __('personnel::common.labels.email'),
            'residental_address' => __('personnel::common.labels.residental_address'),
            'registered_address' => __('personnel::common.labels.registered_address'),
            'structure_id' => __('personnel::common.labels.structure'),
            'position_id' => __('personnel::common.labels.position'),
            'education_degree_id' => __('personnel::common.labels.education_degree'),
            'work_norm_id' => __('personnel::common.labels.work_norms'),
            'social_origin_id' => __('personnel::common.labels.social_origin'),
            'disability_id' => __('personnel::common.labels.disability'),
            'disability_given_date' => __('personnel::common.labels.disability_given_date'),
            'extra_important_information' => __('personnel::common.labels.extra_information'),
            'computer_knowledge' => __('personnel::common.labels.computer_knowledge'),
            'referenced_by' => __('personnel::common.labels.referenced_by'),
            'participation_in_war' => __('personnel::wizard.sections.participation_in_war'),
            'discrediting_information' => __('personnel::wizard.sections.discrediting_information'),
            'is_pending' => __('personnel::common.labels.pending'),
            'added_by' => __('personnel::portfolio.timeline_titles.added_by'),
            'deleted_by' => __('personnel::common.labels.deleted_by'),
            'parent_id' => __('personnel::portfolio.timeline_titles.parent_personnel'),
            'avatar' => __('personnel::common.actions.choose_photo'),
            'status_id' => __('personnel::common.labels.status'),
        ];

        if (isset($explicit[$field])) {
            return $explicit[$field];
        }

        $key = 'personnel::common.labels.'.Str::snake($field);
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        if (Str::endsWith($field, '_id')) {
            return Str::headline(Str::beforeLast($field, '_id'));
        }

        return Str::headline($field);
    }

    private function fieldValueLabel(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('personnel::portfolio.timeline_titles.empty_value');
        }

        $lookup = match ($field) {
            'gender' => $this->genderLabel($value),
            'family_status', 'is_married' => $this->familyStatusLabel($value),
            'status_id' => $this->orderStatusLabel($value),
            'nationality_id', 'previous_nationality_id', 'born_country_id', 'country_id' => $this->countryLabel($value),
            'born_city_id', 'city_id' => $this->tableLabel('cities', $value, 'name'),
            'structure_id', 'sponsor_unit_id' => $this->tableLabel('structures', $value, 'name'),
            'position_id' => $this->tableLabel('positions', $value, 'name'),
            'education_degree_id' => $this->localizedColumnLabel('education_degrees', $value, 'title'),
            'education_type_id' => $this->localizedColumnLabel('education_types', $value, 'name'),
            'education_form_id' => $this->localizedColumnLabel('education_forms', $value, 'name'),
            'education_document_type_id', 'edu_doc_type_id' => $this->localizedColumnLabel('education_document_types', $value, 'name'),
            'educational_institution_id' => $this->tableLabel('educational_institutions', $value, 'name'),
            'work_norm_id' => $this->localizedColumnLabel('work_norms', $value, 'name'),
            'rank_id' => $this->localizedColumnLabel('ranks', $value, 'name'),
            'rank_reason_id' => $this->tableLabel('rank_reasons', $value, 'name'),
            'social_origin_id' => $this->tableLabel('social_origins', $value, 'name'),
            'disability_id' => $this->tableLabel('disabilities', $value, 'name'),
            'kinship_id' => $this->tableLabel('kinships', $value, 'name'),
            'language_id' => $this->tableLabel('languages', $value, 'name'),
            'award_id' => $this->tableLabel('awards', $value, 'name'),
            'punishment_id' => $this->tableLabel('punishments', $value, 'name'),
            'weapon_id' => $this->tableLabel('weapons', $value, 'name'),
            'added_by', 'deleted_by', 'created_by', 'updated_by', 'verified_by' => $this->tableLabel('users', $value, 'name'),
            'parent_id', 'personnel_id', 'approver_personnel_id', 'fallback_approver_personnel_id' => $this->personnelLabel($value),
            default => null,
        };

        if (filled($lookup)) {
            return (string) $lookup;
        }

        if (is_bool($value) || $this->isBooleanField($field)) {
            return $value ? __('personnel::portfolio.timeline_titles.yes') : __('personnel::portfolio.timeline_titles.no');
        }

        if (Str::endsWith($field, '_id')) {
            $genericLookup = $this->genericForeignKeyLabel($field, $value);
            if (filled($genericLookup)) {
                return (string) $genericLookup;
            }
        }

        return is_scalar($value)
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function genderLabel(mixed $value): ?string
    {
        return match ((int) $value) {
            1 => __('personnel::common.labels.man'),
            2 => __('personnel::common.labels.woman'),
            default => null,
        };
    }

    private function familyStatusLabel(mixed $value): ?string
    {
        return match ((int) $value) {
            0 => __('personnel::common.labels.single'),
            1 => __('personnel::common.labels.married'),
            2 => __('personnel::common.labels.married'),
            default => null,
        };
    }

    private function isBooleanField(string $field): bool
    {
        return Str::startsWith($field, ['is_', 'has_', 'can_', 'requires_', 'include_'])
            || in_array($field, ['active', 'verified', 'published'], true);
    }

    private function countryLabel(mixed $id): ?string
    {
        if (! Schema::hasTable('country_translations')) {
            return null;
        }

        $locale = app()->getLocale() ?: 'az';

        return $this->cached("country:{$locale}:{$id}", fn () => DB::table('country_translations')
            ->where('country_id', $id)
            ->whereIn('locale', [$locale, 'az', 'en'])
            ->orderByRaw('case when locale = ? then 0 when locale = ? then 1 else 2 end', [$locale, 'az'])
            ->value('title'));
    }

    private function localizedColumnLabel(string $table, mixed $id, string $baseColumn): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $locale = app()->getLocale() ?: 'az';
        $column = $baseColumn.'_'.($locale === 'az' ? 'az' : $locale);

        if (! Schema::hasColumn($table, $column)) {
            $column = $baseColumn.'_az';
        }

        if (! Schema::hasColumn($table, $column)) {
            return null;
        }

        return $this->cached("{$table}:{$column}:{$id}", fn () => DB::table($table)->where('id', $id)->value($column));
    }

    private function tableLabel(string $table, mixed $id, string $column): ?string
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return null;
        }

        return $this->cached("{$table}:{$column}:{$id}", fn () => DB::table($table)->where('id', $id)->value($column));
    }

    private function orderStatusLabel(mixed $id): ?string
    {
        if (! Schema::hasTable('order_statuses')) {
            return null;
        }

        $locale = app()->getLocale() ?: 'az';

        return $this->cached("order_statuses:{$locale}:{$id}", fn () => DB::table('order_statuses')
            ->where('id', $id)
            ->whereIn('locale', [$locale, 'az', 'en'])
            ->orderByRaw('case when locale = ? then 0 when locale = ? then 1 else 2 end', [$locale, 'az'])
            ->value('name'));
    }

    private function genericForeignKeyLabel(string $field, mixed $id): ?string
    {
        $table = Str::plural(Str::beforeLast($field, '_id'));

        foreach (['name', 'title', 'label', 'code'] as $column) {
            $label = $this->tableLabel($table, $id, $column);
            if (filled($label)) {
                return $label;
            }
        }

        foreach (['name', 'title'] as $baseColumn) {
            $label = $this->localizedColumnLabel($table, $id, $baseColumn);
            if (filled($label)) {
                return $label;
            }
        }

        return null;
    }

    private function personnelLabel(mixed $id): ?string
    {
        if (! Schema::hasTable('personnels')) {
            return null;
        }

        $personnel = $this->cached("personnels:label:{$id}", fn () => DB::table('personnels')
            ->where('id', $id)
            ->first(['surname', 'name', 'patronymic', 'tabel_no']));

        if (! $personnel) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $personnel->surname,
            $personnel->name,
            $personnel->patronymic,
        ]))) ?: (string) $personnel->tabel_no;
    }

    private function cached(string $key, callable $resolver): mixed
    {
        if (array_key_exists($key, $this->lookupCache)) {
            return $this->lookupCache[$key];
        }

        return $this->lookupCache[$key] = $resolver();
    }

    private function auditEventLabel(string $event): string
    {
        $key = 'personnel::portfolio.timeline_events.'.Str::snake($event);
        $label = __($key);

        return $label === $key ? Str::headline($event) : $label;
    }
}
