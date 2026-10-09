<?php

namespace App\Modules\Compliance\Application\Services;

use App\Support\Database\InstalledTables;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Document compliance rows, built as ONE SQL union so the database filters, sorts and
 * pages them: existing documents (service cards, passports, contracts) with their status
 * computed in a CASE, plus one "missing" branch per required document type
 * (personnel with no such document). The dashboard never loads more than a page of rows.
 */
class DocumentExpiryReadService
{
    public const PER_PAGE = 25;

    /**
     * Document type => source table and expiry column. The array order is also the
     * tie-break order of the rows (cards, passports, ID cards, contracts, then missing rows).
     */
    private const DOCUMENT_SOURCES = [
        'service_card' => ['table' => 'personnel_cards', 'expires' => 'valid_date'],
        'passport' => ['table' => 'personnel_passports', 'expires' => 'valid_date'],
        // The ID card's expiry column arrived later than its table; until that migration
        // has run the branch is left out rather than failing the whole union.
        'id_card' => ['table' => 'personnel_identity_documents', 'expires' => 'valid_date', 'column_added_later' => true],
        'contract' => ['table' => 'personnel_contracts', 'expires' => 'contract_ends_at'],
    ];

    private const STATUSES = ['expired', 'expiring_30', 'expiring_60', 'valid', 'missing'];

    /**
     * Statuses that make a row critical: a document past its expiry date or a mandatory
     * document that was never entered. Both leave the employee without a valid document
     * today, unlike the expiring windows, which are warnings with time left to act.
     */
    public const CRITICAL_STATUSES = ['expired', 'missing'];

    /**
     * A required document type that another recorded document also satisfies. Azerbaijani
     * citizens identify with the ID card (şəxsiyyət vəsiqəsi) rather than a passport, so the
     * "passport" requirement counts as met when an identity document with a number exists.
     */
    private const REQUIREMENT_EQUIVALENTS = [
        'passport' => [
            ['table' => 'personnel_identity_documents', 'column' => 'number'],
        ],
    ];

    /**
     * A document type that has no requirement row of its own and takes its day windows
     * from another type's: the ID card (şəxsiyyət vəsiqəsi) is the identity document the
     * "passport" requirement already accepts, so it expires on the same schedule.
     */
    private const WINDOW_FROM = [
        'id_card' => 'passport',
    ];

    /**
     * Day windows used when a document type has no requirement row or a null column.
     * `expiring_30` is the near (renew-now) window, `expiring_60` the early-warning one —
     * both are warnings; only expired and missing rows are critical. The keys stay
     * fixed for filters, counts and exports while the day bounds come per type.
     */
    private const DEFAULT_CRITICAL_DAYS = 30;

    private const DEFAULT_WARNING_DAYS = 60;

    public function dashboard(array $filters = [], int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $requirements = $this->requirements();
        $base = $this->unionQuery($requirements);
        $status = (string) ($filters['status'] ?? '');
        $type = (string) ($filters['type'] ?? '');

        // A facet counts inside the OTHER filters but not inside itself: status numbers keep
        // the type filter and drop the status one, and vice versa. Counting the fully
        // filtered set instead would leave the selected row as the only non-zero one, and
        // the facet could never be clicked back out of.
        $facets = $base === null ? collect() : $this->applySearch(clone $base, $filters['search'] ?? '')
            ->selectRaw('status, document_type, COUNT(*) as aggregate')
            ->groupBy('status', 'document_type')
            ->get();

        $statusScope = $facets->when($type !== '', fn (Collection $rows) => $rows->where('document_type', $type));
        $typeScope = $facets->when($status !== '', fn (Collection $rows) => $rows->where('status', $status));

        $structureStatus = $this->structureStatusCounts($base);
        $requiredTotal = $structureStatus->whereIn('status', self::STATUSES)->sum('aggregate');
        $healthyTotal = $structureStatus->whereIn('status', ['valid', 'expiring_30', 'expiring_60'])->sum('aggregate');

        return [
            'summary' => [
                'total' => (int) $statusScope->sum('aggregate'),
                'expired' => (int) $statusScope->where('status', 'expired')->sum('aggregate'),
                'expiring_30' => (int) $statusScope->where('status', 'expiring_30')->sum('aggregate'),
                'expiring_60' => (int) $statusScope->where('status', 'expiring_60')->sum('aggregate'),
                'valid' => (int) $statusScope->where('status', 'valid')->sum('aggregate'),
                'missing' => (int) $statusScope->where('status', 'missing')->sum('aggregate'),
                'critical' => (int) $statusScope->whereIn('status', self::CRITICAL_STATUSES)->sum('aggregate'),
                'compliance_score' => $requiredTotal > 0 ? (int) round(($healthyTotal / $requiredTotal) * 100) : 100,
            ],
            'typeCounts' => collect(array_keys(self::DOCUMENT_SOURCES))
                ->mapWithKeys(fn (string $key): array => [$key => (int) $typeScope->where('document_type', $key)->sum('aggregate')])
                ->all(),
            'rows' => $this->paginate($base === null ? null : $this->filtered(clone $base, $filters), $page, $perPage),
            'structureScores' => $this->scoresFromCounts($structureStatus),
            'typeWindows' => collect(array_keys(self::DOCUMENT_SOURCES))
                ->mapWithKeys(fn (string $key): array => [$key => $this->window($requirements, $key)])
                ->all(),
        ];
    }

    public function rows(array $filters = []): Collection
    {
        $base = $this->unionQuery($this->requirements());

        if ($base === null) {
            return collect();
        }

        return $this->filtered($base, $filters)->get()->map(fn (object $row): array => $this->shape($row));
    }

    public function exportRows(array $filters = []): Collection
    {
        return $this->rows($filters)->map(fn (array $row): array => [
            'personnel' => $row['personnel_name'],
            'tabel_no' => $row['tabel_no'],
            'structure' => $row['structure_name'],
            'position' => $row['position_name'],
            'document_type' => $row['document_label'],
            'document_number' => $row['document_number'],
            'expires_at' => $row['expires_at'],
            'days_left' => $row['days_left'] ?? '',
            'status' => __('compliance::documents.status.'.$row['status']),
        ]);
    }

    public function reminderRows(int $daysAhead = 30): Collection
    {
        $base = $this->unionQuery($this->requirements());

        if ($base === null) {
            return collect();
        }

        // Only the rows a reminder is about come back from the database: expired, critical or
        // missing, or anything expiring within the look-ahead (Y-m-d strings compare as dates).
        return $base
            ->where(fn (Builder $query) => $query
                ->whereIn('status', ['expired', 'expiring_30', 'missing'])
                ->orWhere(fn (Builder $query) => $query
                    ->whereNotNull('expires_on')
                    ->where('expires_on', '<=', today()->addDays($daysAhead)->toDateString())))
            ->select([
                'record_id', 'document_type', 'document_label', 'document_number', 'expires_on',
                'status', 'tabel_no', 'personnel_name', 'structure_name', 'position_name',
            ])
            ->get()
            ->map(fn (object $row): array => $this->shape($row))
            ->sortBy(fn (array $row): string => ($row['expires_at_sort'] ?? '9999-12-31').'|'.$row['personnel_name'])
            ->values();
    }

    public function structureScores(): Collection
    {
        return $this->scoresFromCounts($this->structureStatusCounts($this->unionQuery($this->requirements())));
    }

    private function paginate(?Builder $query, int $page, int $perPage): LengthAwarePaginator
    {
        $total = $query === null ? 0 : $query->getCountForPagination();
        $page = min(max(1, $page), max(1, (int) ceil($total / $perPage)));

        $rows = $total === 0
            ? collect()
            : $query->forPage($page, $perPage)->get()->map(fn (object $row): array => $this->shape($row));

        return new LengthAwarePaginator($rows, $total, $perPage, $page);
    }

    private function filtered(Builder $query, array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? '');
        $type = (string) ($filters['type'] ?? '');

        return $this->applySearch($query, $filters['search'] ?? '')
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($type !== '', fn (Builder $query) => $query->where('document_type', $type))
            // Byte order, like the old PHP string sort — MySQL's unicode_ci would put Ə next to E.
            ->orderByRaw($this->isMysql() ? 'CAST(sort_text AS BINARY)' : 'sort_text')
            ->orderBy('branch')
            ->orderBy('row_id')
            ->select([
                'record_id', 'document_type', 'document_label', 'document_number', 'expires_on',
                'status', 'tabel_no', 'personnel_name', 'structure_name', 'position_name',
            ]);
    }

    private function applySearch(Builder $query, mixed $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        // ponytail: LOWER()+LIKE folds case per DB — MySQL (unicode_ci) also folds non-ASCII
        // and accents, SQLite only folds ASCII. PHP's mb_strtolower() sat between the two.
        return $query->whereRaw(
            "LOWER(haystack) LIKE LOWER(?) ESCAPE '!'",
            ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%']
        );
    }

    private function structureStatusCounts(?Builder $base): Collection
    {
        if ($base === null) {
            return collect();
        }

        return (clone $base)
            ->selectRaw('structure_name, status, COUNT(*) as aggregate')
            ->groupBy('structure_name', 'status')
            ->get()
            ->map(fn (object $row): object => (object) [
                'structure_name' => (string) $row->structure_name,
                'status' => (string) $row->status,
                'aggregate' => (int) $row->aggregate,
            ]);
    }

    private function scoresFromCounts(Collection $counts): Collection
    {
        return $counts->groupBy('structure_name')
            ->map(function (Collection $rows, string $structureName): array {
                $total = $rows->sum('aggregate');
                $healthy = $rows->whereIn('status', ['valid', 'expiring_30', 'expiring_60'])->sum('aggregate');

                return [
                    'structure_name' => $structureName,
                    'total' => $total,
                    'missing' => $rows->where('status', 'missing')->sum('aggregate'),
                    'expired' => $rows->where('status', 'expired')->sum('aggregate'),
                    'at_risk' => $rows->whereIn('status', ['missing', 'expired', 'expiring_30'])->sum('aggregate'),
                    'score' => $total > 0 ? (int) round(($healthy / $total) * 100) : 100,
                ];
            })
            ->sortBy([
                ['score', 'asc'],
                ['structure_name', 'asc'],
            ])
            ->take(5)
            ->values();
    }

    /**
     * The union of every document branch and every missing-document branch, wrapped so
     * the caller can filter, aggregate and page it. Null when there is nothing to read.
     */
    private function unionQuery(Collection $requirements): ?Builder
    {
        if (! InstalledTables::has('personnels')) {
            return null;
        }

        $branches = [];
        $branch = 0;

        foreach (self::DOCUMENT_SOURCES as $type => $source) {
            $installed = ($source['column_added_later'] ?? false)
                ? InstalledTables::hasColumn($source['table'], $source['expires'])
                : InstalledTables::has($source['table']);

            if ($installed) {
                $branches[] = $this->documentBranch($branch, $type, $source['table'], $source['expires'], $this->window($requirements, $type));
            }
            $branch++;
        }

        foreach ($requirements->where('is_required', true) as $requirement) {
            $branches[] = $this->missingBranch($branch++, $requirement);
        }

        if ($branches === []) {
            return null;
        }

        $union = array_shift($branches);

        foreach ($branches as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, 'compliance_rows');
    }

    /**
     * @param  array{critical: int, warning: int}  $window
     */
    private function documentBranch(int $branch, string $type, string $table, string $expiresColumn, array $window): Builder
    {
        $label = __('compliance::documents.types.'.$type);
        $expires = "NULLIF(SUBSTR({$table}.{$expiresColumn}, 1, 10), '')";
        [$number, $numberBindings] = $this->documentNumberSql($type, $table);

        // < 0 days expired, <= critical days expiring_30, <= warning days expiring_60, else
        // valid; no expiry date is valid. The type's day windows are baked in as literal
        // bound dates, compared as Y-m-d strings against PHP's today().
        $status = "CASE WHEN {$expires} IS NULL THEN 'valid'"
            ." WHEN {$expires} < ? THEN 'expired'"
            ." WHEN {$expires} <= ? THEN 'expiring_30'"
            ." WHEN {$expires} <= ? THEN 'expiring_60'"
            ." ELSE 'valid' END";
        $statusBindings = [
            today()->toDateString(),
            today()->addDays($window['critical'])->toDateString(),
            today()->addDays($window['warning'])->toDateString(),
        ];

        $query = DB::table($table)
            ->join('personnels', 'personnels.tabel_no', '=', "{$table}.tabel_no")
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id');

        $this->onlyActivePersonnel($query);

        if ($type === 'contract') {
            $query->leftJoin('ranks', 'ranks.id', '=', "{$table}.rank_id");
        }

        return $this->selectColumns($query, [
            'branch' => ['?', [$branch]],
            'row_id' => ["{$table}.id", []],
            'record_id' => ["{$table}.id", []],
            'document_type' => ['?', [$type]],
            'document_label' => ['?', [$label]],
            'document_number' => [$number, $numberBindings],
            'expires_on' => [$expires, []],
            'status' => [$status, $statusBindings],
            'sort_key' => ["COALESCE({$expires}, '9999-12-31')", []],
        ]);
    }

    private function missingBranch(int $branch, array $requirement): Builder
    {
        $query = DB::table('personnels')
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id');

        $this->onlyActivePersonnel($query);

        $source = self::DOCUMENT_SOURCES[$requirement['key']] ?? null;

        if ($source !== null && InstalledTables::has($source['table'])) {
            $query->whereNotExists(fn (Builder $documents) => $documents
                ->selectRaw('1')
                ->from($source['table'])
                ->whereColumn("{$source['table']}.tabel_no", 'personnels.tabel_no'));
        }

        foreach (self::REQUIREMENT_EQUIVALENTS[$requirement['key']] ?? [] as $equivalent) {
            if (! InstalledTables::has($equivalent['table'])) {
                continue;
            }

            $query->whereNotExists(fn (Builder $documents) => $documents
                ->selectRaw('1')
                ->from($equivalent['table'])
                ->whereColumn("{$equivalent['table']}.tabel_no", 'personnels.tabel_no')
                ->whereNotNull("{$equivalent['table']}.{$equivalent['column']}")
                ->where("{$equivalent['table']}.{$equivalent['column']}", '!=', ''));
        }

        return $this->selectColumns($query, [
            'branch' => ['?', [$branch]],
            'row_id' => ['personnels.id', []],
            'record_id' => ['NULL', []],
            'document_type' => ['?', [$requirement['key']]],
            'document_label' => ['?', [$requirement['label']]],
            'document_number' => ['?', [__('compliance::documents.labels.required_document')]],
            'expires_on' => ['NULL', []],
            'status' => ["'missing'", []],
            'sort_key' => ["'0000-00-00'", []],
        ]);
    }

    /**
     * Compliance is about the people currently employed: soft-deleted, still-pending and
     * already dismissed personnel neither owe a document nor make one critical.
     */
    private function onlyActivePersonnel(Builder $query): void
    {
        $query->whereNull('personnels.deleted_at')
            ->where('personnels.is_pending', false)
            ->where(fn (Builder $active) => $active
                ->whereNull('personnels.leave_work_date')
                ->orWhere('personnels.leave_work_date', '>=', today()->toDateString()));
    }

    /**
     * Adds the personnel columns plus the derived sort / search columns, so every branch
     * selects the same column list in the same order.
     *
     * @param  array<string, array{0: string, 1: array<int, mixed>}>  $columns
     */
    private function selectColumns(Builder $query, array $columns): Builder
    {
        $unassigned = __('compliance::documents.labels.unassigned');

        $columns += [
            'tabel_no' => ["COALESCE(personnels.tabel_no, '')", []],
            'personnel_name' => [$this->personnelNameSql(), []],
            'structure_name' => ["COALESCE(NULLIF(structures.name, ''), ?)", [$unassigned]],
            'position_name' => ["COALESCE(NULLIF(positions.name, ''), ?)", [$unassigned]],
        ];

        // Old PHP sort key: expires_at_sort|personnel_name|document_label.
        $columns['sort_text'] = [
            $this->concat($columns['sort_key'][0], "'|'", $columns['personnel_name'][0], "'|'", $columns['document_label'][0]),
            [...$columns['sort_key'][1], ...$columns['personnel_name'][1], ...$columns['document_label'][1]],
        ];

        // Old PHP search haystack: the six visible fields joined by a space.
        $haystackParts = ['personnel_name', 'tabel_no', 'document_label', 'document_number', 'structure_name', 'position_name'];
        $haystackSql = [];
        $haystackBindings = [];
        foreach ($haystackParts as $index => $part) {
            if ($index > 0) {
                $haystackSql[] = "' '";
            }
            $haystackSql[] = $columns[$part][0];
            array_push($haystackBindings, ...$columns[$part][1]);
        }
        $columns['haystack'] = [$this->concat(...$haystackSql), $haystackBindings];

        unset($columns['sort_key']);

        $sql = [];
        $bindings = [];
        foreach ($columns as $alias => [$expression, $expressionBindings]) {
            $sql[] = "{$expression} as {$alias}";
            array_push($bindings, ...$expressionBindings);
        }

        return $query->selectRaw(implode(', ', $sql), $bindings);
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function documentNumberSql(string $type, string $table): array
    {
        return match ($type) {
            'service_card' => ["COALESCE({$table}.card_number, '')", []],
            'passport' => ["COALESCE({$table}.serial_number, '')", []],
            'id_card' => ['TRIM('.$this->concat("COALESCE({$table}.series, '')", "' '", "COALESCE({$table}.number, '')").')', []],
            'contract' => $this->contractNumberSql($table),
            default => throw new InvalidArgumentException("Unknown document type [{$type}]."),
        };
    }

    /**
     * "rank · From <date> · <n> months", skipping empty parts — the old PHP
     * implode(' · ', array_filter([...])), with the translated templates split around
     * their placeholder.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private function contractNumberSql(string $table): array
    {
        [$fromPrefix, $fromSuffix] = explode("\u{0}", __('compliance::documents.labels.contract_from', ['date' => "\u{0}"]), 2) + [1 => ''];
        [$durationPrefix, $durationSuffix] = explode("\u{0}", __('compliance::documents.labels.contract_duration', ['months' => "\u{0}"]), 2) + [1 => ''];

        $separator = "' · '";
        $rank = "CASE WHEN COALESCE(ranks.name_az, '') = '' THEN '' ELSE ".$this->concat($separator, 'ranks.name_az').' END';
        $from = "CASE WHEN {$table}.contract_date IS NULL THEN '' ELSE ".$this->concat($separator, '?', "{$table}.contract_date", '?').' END';
        $duration = "CASE WHEN COALESCE({$table}.contract_duration, 0) = 0 THEN '' ELSE "
            .$this->concat($separator, '?', "CAST({$table}.contract_duration AS CHAR)", '?').' END';

        return [
            'TRIM(SUBSTR('.$this->concat($rank, $from, $duration).', 4))',
            [$fromPrefix, $fromSuffix, $durationPrefix, $durationSuffix],
        ];
    }

    private function personnelNameSql(): string
    {
        $part = fn (string $column): string => "CASE WHEN COALESCE(personnels.{$column}, '') = '' THEN '' ELSE "
            .$this->concat("' '", "personnels.{$column}").' END';

        return 'TRIM('.$this->concat($part('surname'), $part('name'), $part('patronymic')).')';
    }

    /**
     * MySQL concatenates with CONCAT(), SQLite with ||.
     * Every part must already be non-null.
     */
    private function concat(string ...$parts): string
    {
        return $this->isMysql()
            ? 'CONCAT('.implode(', ', $parts).')'
            : '('.implode(' || ', $parts).')';
    }

    private function isMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function requirements(): Collection
    {
        if (! InstalledTables::has('compliance_document_requirements')) {
            return collect(array_keys(self::DOCUMENT_SOURCES))->map(fn (string $key): array => [
                'key' => $key,
                'label' => __('compliance::documents.types.'.$key),
                'is_required' => true,
                'critical_days' => null,
                'warning_days' => null,
            ]);
        }

        $localeColumn = app()->getLocale() === 'en' ? 'label_en' : 'label_az';

        return DB::table('compliance_document_requirements')
            ->select(['key', 'label_az', 'label_en', 'is_required', 'critical_days', 'warning_days'])
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => [
                'key' => (string) $row->key,
                'label' => (string) ($row->{$localeColumn} ?: $row->label_az),
                'is_required' => (bool) $row->is_required,
                'critical_days' => $row->critical_days !== null ? (int) $row->critical_days : null,
                'warning_days' => $row->warning_days !== null ? (int) $row->warning_days : null,
            ]);
    }

    /**
     * The type's critical / warning day windows, falling back to 30 / 60.
     *
     * @return array{critical: int, warning: int}
     */
    private function window(Collection $requirements, string $type): array
    {
        $requirement = $requirements->firstWhere('key', $type)
            ?? (isset(self::WINDOW_FROM[$type]) ? $requirements->firstWhere('key', self::WINDOW_FROM[$type]) : null);
        $critical = $requirement['critical_days'] ?? self::DEFAULT_CRITICAL_DAYS;

        // A warning window shorter than the critical one would be empty anyway; show it as such.
        return [
            'critical' => $critical,
            'warning' => max($critical, $requirement['warning_days'] ?? self::DEFAULT_WARNING_DAYS),
        ];
    }

    private function shape(object $row): array
    {
        $missing = $row->status === 'missing';
        $expiresAt = $row->expires_on !== null ? Carbon::parse($row->expires_on)->startOfDay() : null;

        return [
            'document_type' => (string) $row->document_type,
            'document_label' => (string) $row->document_label,
            'record_id' => $row->record_id !== null ? (int) $row->record_id : null,
            'document_number' => (string) $row->document_number,
            'expires_at' => $missing
                ? __('compliance::documents.labels.not_available')
                : ($expiresAt?->toDateString() ?: __('compliance::documents.labels.indefinite')),
            'expires_at_sort' => $missing ? '0000-00-00' : ($expiresAt?->toDateString() ?: '9999-12-31'),
            'days_left' => $expiresAt !== null ? (int) today()->diffInDays($expiresAt, false) : null,
            'status' => (string) $row->status,
            'tabel_no' => (string) $row->tabel_no,
            'personnel_name' => (string) $row->personnel_name,
            'structure_name' => (string) $row->structure_name,
            'position_name' => (string) $row->position_name,
        ];
    }
}
