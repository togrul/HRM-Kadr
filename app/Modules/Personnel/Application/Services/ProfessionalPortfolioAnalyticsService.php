<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Models\ProfessionalEventRegistry;
use App\Models\ProfessionalMediaOutletRegistry;
use App\Models\ProfessionalProjectRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfessionalPortfolioAnalyticsService
{
    public function build(Personnel $personnel, array $filters = []): array
    {
        $status = (string) ($filters['status'] ?? 'verified');
        $dateFrom = filled($filters['date_from'] ?? null) ? (string) $filters['date_from'] : null;
        $dateTo = filled($filters['date_to'] ?? null) ? (string) $filters['date_to'] : null;

        $eventQuery = $this->applyDateAndStatusFilters($personnel->eventRecords(), 'start_date', $status, $dateFrom, $dateTo);
        $mediaQuery = $this->applyDateAndStatusFilters($personnel->mediaMentions(), 'published_at', $status, $dateFrom, $dateTo);
        $projectQuery = $this->applyDateAndStatusFilters($personnel->projectRecords(), 'start_date', $status, $dateFrom, $dateTo);

        $eventMetrics = $this->eventMetrics($eventQuery);
        $mediaMetrics = $this->mediaMetrics($mediaQuery);
        $projectMetrics = $this->projectMetrics($projectQuery);
        $registryMasters = $this->registryMasterCounts();
        $yearlyActivity = $this->buildYearlyActivity($eventQuery, $mediaQuery, $projectQuery);
        $mediaBreakdowns = $this->buildMediaBreakdowns($mediaQuery);

        $cards = [
            [
                'label' => __('personnel::portfolio.analytics.total_records'),
                'value' => $eventMetrics['total'] + $mediaMetrics['total'] + $projectMetrics['total'],
            ],
            [
                'label' => __('personnel::portfolio.analytics.speaker_records'),
                'value' => $eventMetrics['speaker_records'],
            ],
            [
                'label' => __('personnel::portfolio.analytics.public_mentions'),
                'value' => $mediaMetrics['public_mentions'],
            ],
            [
                'label' => __('personnel::portfolio.analytics.ongoing_projects'),
                'value' => $projectMetrics['ongoing_projects'],
            ],
            [
                'label' => __('personnel::portfolio.analytics.broken_links'),
                'value' => $mediaMetrics['broken_links'],
            ],
            [
                'label' => __('personnel::portfolio.analytics.archive_issues'),
                'value' => $mediaMetrics['archive_issues'],
            ],
        ];

        return [
            'cards' => $cards,
            'yearly_activity' => $yearlyActivity,
            'event_roles' => $this->buildSimpleBreakdown(
                (clone $eventQuery)
                    ->select('participation_role as bucket')
                    ->selectRaw('COUNT(*) as aggregate_count')
                    ->groupBy('participation_role')
                    ->orderByDesc('aggregate_count')
                    ->get(),
                fn (object $row): string => __('personnel::portfolio.options.participation_role.'.$row->bucket)
            ),
            'media_publishers' => $this->buildSimpleBreakdown(
                (clone $mediaQuery)
                    ->select('publisher_name as bucket')
                    ->selectRaw('COUNT(*) as aggregate_count')
                    ->groupBy('publisher_name')
                    ->orderByDesc('aggregate_count')
                    ->limit(5)
                    ->get(),
                fn (object $row): string => (string) $row->bucket
            ),
            'project_sponsors' => $this->buildSimpleBreakdown(
                (clone $projectQuery)
                    ->leftJoin('structures', 'structures.id', '=', 'personnel_project_records.sponsor_unit_id')
                    ->selectRaw('COALESCE(structures.name, ?) as bucket', [__('personnel::portfolio.analytics.unassigned')])
                    ->selectRaw('COUNT(*) as aggregate_count')
                    ->groupBy('bucket')
                    ->orderByDesc('aggregate_count')
                    ->limit(5)
                    ->get(),
                fn (object $row): string => (string) $row->bucket
            ),
            'status_mix' => $status === 'all'
                ? $this->buildStatusMix($personnel, $dateFrom, $dateTo)
                : $this->singleStatusMix($status, $eventMetrics['total'] + $mediaMetrics['total'] + $projectMetrics['total']),
            'registry_readiness' => [
                'event_clusters' => $eventMetrics['registry_clusters'],
                'media_outlets' => $mediaMetrics['registry_outlets'],
                'project_clusters' => $projectMetrics['registry_clusters'],
            ],
            'visibility_mix' => $mediaBreakdowns['visibility_mix'],
            'media_health_mix' => $mediaBreakdowns['media_health_mix'],
            'approval_backlog' => [
                'events' => $eventMetrics['pending_records'],
                'media' => $mediaMetrics['pending_records'],
                'projects' => $projectMetrics['pending_records'],
            ],
            'registry_masters' => $registryMasters,
        ];
    }

    private function eventMetrics(Builder|Relation $query): array
    {
        $row = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN participation_role IN ('speaker','moderator','panelist','presenter') THEN 1 ELSE 0 END) as speaker_records")
            ->selectRaw('COUNT(DISTINCT registry_key) as registry_clusters')
            ->selectRaw("SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_records")
            ->first();

        return $this->normalizeMetricRow($row, [
            'total',
            'speaker_records',
            'registry_clusters',
            'pending_records',
        ]);
    }

    private function mediaMetrics(Builder|Relation $query): array
    {
        $row = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN visibility = 'public' THEN 1 ELSE 0 END) as public_mentions")
            ->selectRaw("SUM(CASE WHEN verification_status = 'broken_link' THEN 1 ELSE 0 END) as broken_links")
            ->selectRaw("SUM(CASE WHEN archive_health_status = 'missing' THEN 1 ELSE 0 END) as archive_issues")
            ->selectRaw('COUNT(DISTINCT publisher_registry_key) as registry_outlets')
            ->selectRaw("SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_records")
            ->first();

        return $this->normalizeMetricRow($row, [
            'total',
            'public_mentions',
            'broken_links',
            'archive_issues',
            'registry_outlets',
            'pending_records',
        ]);
    }

    private function projectMetrics(Builder|Relation $query): array
    {
        $row = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN is_ongoing = 1 THEN 1 ELSE 0 END) as ongoing_projects")
            ->selectRaw('COUNT(DISTINCT registry_key) as registry_clusters')
            ->selectRaw("SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_records")
            ->first();

        return $this->normalizeMetricRow($row, [
            'total',
            'ongoing_projects',
            'registry_clusters',
            'pending_records',
        ]);
    }

    private function registryMasterCounts(): array
    {
        $row = Cache::remember('personnel.professional_portfolio.registry_master_counts', now()->addMinutes(5), function () {
            return DB::query()
                ->selectRaw('(SELECT COUNT(*) FROM '.(new ProfessionalEventRegistry())->getTable().') as events')
                ->selectRaw('(SELECT COUNT(*) FROM '.(new ProfessionalMediaOutletRegistry())->getTable().') as media_outlets')
                ->selectRaw('(SELECT COUNT(*) FROM '.(new ProfessionalProjectRegistry())->getTable().') as projects')
                ->first();
        });

        return [
            'events' => (int) ($row->events ?? 0),
            'media_outlets' => (int) ($row->media_outlets ?? 0),
            'projects' => (int) ($row->projects ?? 0),
        ];
    }

    private function applyDateAndStatusFilters(Builder|Relation $query, string $dateColumn, string $status, ?string $dateFrom, ?string $dateTo): Builder|Relation
    {
        return $query
            ->when($status !== 'all', fn ($builder) => $builder->where('verification_status', $status))
            ->when($dateFrom, fn ($builder) => $builder->whereDate($dateColumn, '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate($dateColumn, '<=', $dateTo));
    }

    private function buildYearlyActivity(Builder|Relation $eventQuery, Builder|Relation $mediaQuery, Builder|Relation $projectQuery): Collection
    {
        $yearExpression = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', occurred_at) AS INTEGER)"
            : 'YEAR(occurred_at)';

        $events = (clone $eventQuery)
            ->whereNotNull('start_date')
            ->selectRaw("'events' as bucket_type, start_date as occurred_at");
        $media = (clone $mediaQuery)
            ->whereNotNull('published_at')
            ->selectRaw("'media' as bucket_type, published_at as occurred_at");
        $projects = (clone $projectQuery)
            ->whereNotNull('start_date')
            ->selectRaw("'projects' as bucket_type, start_date as occurred_at");

        $rows = DB::query()
            ->fromSub($events->unionAll($media)->unionAll($projects), 'portfolio_years')
            ->selectRaw($yearExpression.' as year_key, bucket_type, COUNT(*) as aggregate_count')
            ->groupBy('year_key', 'bucket_type')
            ->orderByDesc('year_key')
            ->get()
            ->groupBy(fn (object $row) => (int) $row->year_key);

        return $rows->map(function (Collection $group, int $year) {
            $mapped = $group->mapWithKeys(fn (object $row) => [$row->bucket_type => (int) $row->aggregate_count]);

            return [
                'year' => $year,
                'events' => (int) ($mapped['events'] ?? 0),
                'media' => (int) ($mapped['media'] ?? 0),
                'projects' => (int) ($mapped['projects'] ?? 0),
                'total' => (int) (($mapped['events'] ?? 0) + ($mapped['media'] ?? 0) + ($mapped['projects'] ?? 0)),
            ];
        })->sortByDesc('year')->values();
    }

    private function buildStatusMix(Personnel $personnel, ?string $dateFrom, ?string $dateTo): Collection
    {
        $query = DB::query()
            ->fromSub($this->statusUnion($personnel, $dateFrom, $dateTo), 'portfolio_statuses')
            ->select('verification_status')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('verification_status')
            ->orderByDesc('aggregate_count')
            ->get();

        return $query->map(fn ($row) => [
            'label' => __('personnel::portfolio.status.'.$row->verification_status),
            'value' => (int) $row->aggregate_count,
        ]);
    }

    private function statusUnion(Personnel $personnel, ?string $dateFrom, ?string $dateTo): Builder|Relation
    {
        $events = $this->applyDateAndStatusFilters(
            $personnel->eventRecords()->selectRaw('verification_status, start_date as occurred_at'),
            'start_date',
            'all',
            $dateFrom,
            $dateTo
        );
        $media = $this->applyDateAndStatusFilters(
            $personnel->mediaMentions()->selectRaw('verification_status, published_at as occurred_at'),
            'published_at',
            'all',
            $dateFrom,
            $dateTo
        );
        $projects = $this->applyDateAndStatusFilters(
            $personnel->projectRecords()->selectRaw('verification_status, start_date as occurred_at'),
            'start_date',
            'all',
            $dateFrom,
            $dateTo
        );

        return $events->unionAll($media)->unionAll($projects);
    }

    private function buildMediaHealthMix(Personnel $personnel): Collection
    {
        return $this->buildMediaBreakdowns($personnel->mediaMentions())['media_health_mix'];
    }

    private function buildMediaBreakdowns(Builder|Relation $mediaQuery): array
    {
        $visibilityRows = (clone $mediaQuery)
            ->selectRaw("'visibility' as bucket_group")
            ->selectRaw('visibility as bucket')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('visibility');

        $healthRows = (clone $mediaQuery)
            ->whereNotNull('link_check_status')
            ->selectRaw("'health' as bucket_group")
            ->selectRaw('link_check_status as bucket')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('link_check_status');

        $rows = $visibilityRows
            ->unionAll($healthRows)
            ->orderByDesc('aggregate_count')
            ->get()
            ->groupBy('bucket_group');

        return [
            'visibility_mix' => $this->buildSimpleBreakdown(
                $rows->get('visibility', collect()),
                fn (object $row): string => __('personnel::portfolio.options.visibility.'.$row->bucket)
            ),
            'media_health_mix' => $this->buildSimpleBreakdown(
                $rows->get('health', collect()),
                fn (object $row): string => __('personnel::portfolio.health.link.'.$row->bucket)
            ),
        ];
    }

    private function singleStatusMix(string $status, int $total): Collection
    {
        if ($total === 0) {
            return collect();
        }

        return collect([[
            'label' => __('personnel::portfolio.status.'.$status),
            'value' => $total,
        ]]);
    }

    private function buildSimpleBreakdown(Collection $rows, callable $labelResolver): Collection
    {
        return $rows->map(fn (object $row) => [
            'label' => $labelResolver($row),
            'value' => (int) $row->aggregate_count,
        ]);
    }

    private function normalizeMetricRow(?object $row, array $keys): array
    {
        $metrics = [];

        foreach ($keys as $key) {
            $metrics[$key] = (int) ($row->{$key} ?? 0);
        }

        return $metrics;
    }
}
