<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\CandidateApplication;
use App\Models\CandidateStageEvent;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RecruitmentAnalytics extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;

    protected const ANALYTICS_LABEL_DEFAULTS = [
        'requisitions' => ['az' => 'Tələbnamələr', 'en' => 'Requisitions'],
        'openings' => ['az' => 'Vakansiyalar', 'en' => 'Openings'],
        'applications' => ['az' => 'Müraciətlər', 'en' => 'Applications'],
        'active_applications' => ['az' => 'Aktiv müraciətlər', 'en' => 'Active applications'],
        'rejected_applications' => ['az' => 'Rədd edilən müraciətlər', 'en' => 'Rejected applications'],
        'successful_applications' => ['az' => 'Uğurlu yekun müraciətlər', 'en' => 'Successful applications'],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', CandidateApplication::class);
    }

    #[Computed]
    public function currentPack(): string
    {
        return $this->workflowPackResolver()->resolve();
    }

    #[Computed]
    public function summary(): array
    {
        $row = CandidateApplication::query()
            ->join('job_openings', 'job_openings.id', '=', 'candidate_applications.job_opening_id')
            ->where('job_openings.profile_pack', $this->currentPack)
            ->selectRaw('COUNT(*) as total_applications')
            ->selectRaw("SUM(CASE WHEN candidate_applications.status = 'active' THEN 1 ELSE 0 END) as active_applications")
            ->selectRaw("SUM(CASE WHEN candidate_applications.status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications")
            ->selectRaw("SUM(CASE WHEN candidate_applications.current_stage IN ('hired', 'appointed') THEN 1 ELSE 0 END) as successful_applications")
            ->first();

        return [
            'requisitions' => JobRequisition::query()->where('profile_pack', $this->currentPack)->count(),
            'openings' => JobOpening::query()->where('profile_pack', $this->currentPack)->count(),
            'applications' => (int) ($row?->total_applications ?? 0),
            'active_applications' => (int) ($row?->active_applications ?? 0),
            'rejected_applications' => (int) ($row?->rejected_applications ?? 0),
            'successful_applications' => (int) ($row?->successful_applications ?? 0),
        ];
    }

    #[Computed]
    public function summaryCards(): array
    {
        return [
            [
                'key' => 'requisitions',
                'value' => $this->summary['requisitions'],
                'label' => $this->analyticsLabel('requisitions'),
                'card' => 'border-slate-200 bg-slate-50',
                'labelColor' => 'text-slate-400',
                'valueColor' => 'text-slate-900',
            ],
            [
                'key' => 'openings',
                'value' => $this->summary['openings'],
                'label' => $this->analyticsLabel('openings'),
                'card' => 'border-slate-200 bg-slate-50',
                'labelColor' => 'text-slate-400',
                'valueColor' => 'text-slate-900',
            ],
            [
                'key' => 'applications',
                'value' => $this->summary['applications'],
                'label' => $this->analyticsLabel('applications'),
                'card' => 'border-slate-200 bg-slate-50',
                'labelColor' => 'text-slate-400',
                'valueColor' => 'text-slate-900',
            ],
            [
                'key' => 'active_applications',
                'value' => $this->summary['active_applications'],
                'label' => $this->analyticsLabel('active_applications'),
                'card' => 'border-slate-200 bg-emerald-50',
                'labelColor' => 'text-emerald-600',
                'valueColor' => 'text-emerald-700',
            ],
            [
                'key' => 'rejected_applications',
                'value' => $this->summary['rejected_applications'],
                'label' => $this->analyticsLabel('rejected_applications'),
                'card' => 'border-slate-200 bg-rose-50',
                'labelColor' => 'text-rose-600',
                'valueColor' => 'text-rose-700',
            ],
            [
                'key' => 'successful_applications',
                'value' => $this->summary['successful_applications'],
                'label' => $this->analyticsLabel('successful_applications'),
                'card' => 'border-slate-200 bg-sky-50',
                'labelColor' => 'text-sky-600',
                'valueColor' => 'text-sky-700',
            ],
        ];
    }

    #[Computed]
    public function stageSummary(): array
    {
        $counts = CandidateApplication::query()
            ->join('job_openings', 'job_openings.id', '=', 'candidate_applications.job_opening_id')
            ->where('job_openings.profile_pack', $this->currentPack)
            ->groupBy('candidate_applications.current_stage')
            ->pluck(DB::raw('COUNT(*) as aggregate'), 'candidate_applications.current_stage');

        return collect(app(CandidateApplicationStageService::class)->stagesForPack($this->currentPack))
            ->map(fn (array $stage) => [
                'key' => $stage['key'],
                'label' => $stage['label'],
                'count' => (int) ($counts[$stage['key']] ?? 0),
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function sourceSummary(): array
    {
        return CandidateApplication::query()
            ->join('job_openings', 'job_openings.id', '=', 'candidate_applications.job_opening_id')
            ->leftJoin('candidate_sources', 'candidate_sources.id', '=', 'candidate_applications.candidate_source_id')
            ->where('job_openings.profile_pack', $this->currentPack)
            ->groupBy('candidate_sources.id', 'candidate_sources.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get([
                DB::raw('COALESCE(candidate_sources.name, "—") as label'),
                DB::raw('COUNT(*) as aggregate'),
            ])
            ->map(fn ($row) => ['label' => (string) $row->label, 'count' => (int) $row->aggregate])
            ->all();
    }

    #[Computed]
    public function sourceEffectivenessSummary(): array
    {
        return CandidateApplication::query()
            ->join('job_openings', 'job_openings.id', '=', 'candidate_applications.job_opening_id')
            ->leftJoin('candidate_sources', 'candidate_sources.id', '=', 'candidate_applications.candidate_source_id')
            ->where('job_openings.profile_pack', $this->currentPack)
            ->groupBy('candidate_sources.id', 'candidate_sources.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get([
                DB::raw('COALESCE(candidate_sources.name, "—") as label'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN candidate_applications.current_stage IN ('hired', 'appointed') THEN 1 ELSE 0 END) as successful"),
                DB::raw("SUM(CASE WHEN candidate_applications.status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
            ])
            ->map(function ($row): array {
                $total = (int) $row->total;
                $successful = (int) $row->successful;

                return [
                    'label' => (string) $row->label,
                    'total' => $total,
                    'successful' => $successful,
                    'rejected' => (int) $row->rejected,
                    'success_rate' => $total > 0 ? (int) round(($successful / $total) * 100) : 0,
                ];
            })
            ->all();
    }

    #[Computed]
    public function timeToStageSummary(): array
    {
        $rows = CandidateApplication::query()
            ->join('job_openings', 'job_openings.id', '=', 'candidate_applications.job_opening_id')
            ->where('job_openings.profile_pack', $this->currentPack)
            ->whereNotNull('candidate_applications.applied_at')
            ->get([
                'candidate_applications.current_stage',
                'candidate_applications.applied_at',
                'candidate_applications.moved_at',
                'candidate_applications.hired_at',
                'candidate_applications.rejected_at',
                'candidate_applications.withdrawn_at',
            ])
            ->groupBy('current_stage')
            ->map(function ($group): array {
                $days = $group->map(function ($application): float {
                    $appliedAt = $application->applied_at;
                    $completedAt = $application->hired_at
                        ?? $application->rejected_at
                        ?? $application->withdrawn_at
                        ?? $application->moved_at
                        ?? $application->applied_at;

                    if (! $appliedAt || ! $completedAt) {
                        return 0.0;
                    }

                    return (float) max($appliedAt->diffInDays($completedAt), 0);
                });

                return [
                    'avg_days' => round($days->avg() ?? 0, 1),
                    'total' => $group->count(),
                ];
            });

        return collect($this->stageDefinitionsForCurrentPack())
            ->map(function (array $stage) use ($rows): array {
                $row = $rows->get($stage['key']);

                return [
                    'key' => $stage['key'],
                    'label' => $stage['label'],
                    'avg_days' => (float) ($row['avg_days'] ?? 0.0),
                    'total' => (int) ($row['total'] ?? 0),
                    'terminal' => (bool) ($stage['terminal'] ?? false),
                ];
            })
            ->filter(fn (array $row) => $row['total'] > 0)
            ->values()
            ->all();
    }

    #[Computed]
    public function rejectionReasonSummary(): array
    {
        return CandidateApplication::query()
            ->join('job_openings', 'job_openings.id', '=', 'candidate_applications.job_opening_id')
            ->leftJoin('candidate_rejection_reasons', 'candidate_rejection_reasons.id', '=', 'candidate_applications.rejection_reason_id')
            ->where('job_openings.profile_pack', $this->currentPack)
            ->where('candidate_applications.status', 'rejected')
            ->groupBy('candidate_rejection_reasons.id', 'candidate_rejection_reasons.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get([
                DB::raw('COALESCE(candidate_rejection_reasons.name, "—") as label'),
                DB::raw('COUNT(*) as total'),
            ])
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'count' => (int) $row->total,
            ])
            ->all();
    }

    #[Computed]
    public function recentMoves(): array
    {
        return CandidateStageEvent::query()
            ->with([
                'application:id,candidate_id,job_opening_id,current_stage,status',
                'application.candidate:id,name,surname,patronymic',
                'application.opening:id,title,profile_pack',
                'actor:id,name',
            ])
            ->whereHas('application.opening', fn ($query) => $query->where('profile_pack', $this->currentPack))
            ->latest('occurred_at')
            ->latest('id')
            ->limit(8)
            ->get()
            ->all();
    }

    public function render()
    {
        return view('candidates::livewire.candidates.recruitment-analytics');
    }

    protected function analyticsLabel(string $key): string
    {
        $translated = __('candidates::recruitment.labels.'.$key);

        if ($translated !== 'candidates::recruitment.labels.'.$key) {
            return $translated;
        }

        $locale = app()->getLocale();

        return self::ANALYTICS_LABEL_DEFAULTS[$key][$locale]
            ?? self::ANALYTICS_LABEL_DEFAULTS[$key]['en']
            ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * @return array<int, array{key:string,label:string,terminal?:bool}>
     */
    protected function stageDefinitionsForCurrentPack(): array
    {
        return app(CandidateApplicationStageService::class)->stagesForPack($this->currentPack);
    }
}
