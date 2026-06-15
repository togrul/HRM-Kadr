<?php

namespace App\Modules\Candidates\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Modules\Candidates\Livewire\CandidateList;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Support\Facades\Cache;

class CandidateListRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'candidates:list-render-benchmark
        {--render-response-budget= : Max response size for candidate list render}
        {--render-ms-budget= : Max render time in ms for candidate list render}
        {--filter-response-budget= : Max response size for candidate filter update}
        {--filter-ms-budget= : Max render time in ms for candidate filter update}
        {--modal-response-budget= : Max response size for add candidate modal open}
        {--modal-ms-budget= : Max render time in ms for add candidate modal open}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Candidate list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveUserForPermissions('show-candidates');

        if (! $user) {
            $this->error('No user with Candidate view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-candidates', 'add-candidates') ?? $user;

        $budgets = [
            'candidate_list_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('candidates.performance.render_budget.candidate_list_render.response_bytes', 200000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('candidates.performance.render_budget.candidate_list_render.render_ms', 200))),
            ],
            'candidate_filter_update' => [
                'response_bytes' => max(1, (int) ($this->option('filter-response-budget') ?: config('candidates.performance.render_budget.candidate_filter_update.response_bytes', 220000))),
                'render_ms' => max(1, (float) ($this->option('filter-ms-budget') ?: config('candidates.performance.render_budget.candidate_filter_update.render_ms', 220))),
            ],
            'candidate_add_modal_open' => [
                'response_bytes' => max(1, (int) ($this->option('modal-response-budget') ?: config('candidates.performance.render_budget.candidate_add_modal_open.response_bytes', 150000))),
                'render_ms' => max(1, (float) ($this->option('modal-ms-budget') ?: config('candidates.performance.render_budget.candidate_add_modal_open.render_ms', 150))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('candidate_list_render', $budgets['candidate_list_render'], function () use ($profiler, $user) {
            $this->prepareListEnvironment();

            return $profiler->measureRender($user, CandidateList::class);
        });
        $results[] = $this->probe('candidate_filter_update', $budgets['candidate_filter_update'], function () use ($profiler, $user) {
            $this->prepareListEnvironment();

            return $profiler->measureInteraction(
                $user,
                CandidateList::class,
                fn ($component) => $component
                    ->call('setStatus', 'all')
                    ->set('filter.fullname', 'Ali')
                    ->call('searchFilter')
            );
        });
        $results[] = $this->probe('candidate_add_modal_open', $budgets['candidate_add_modal_open'], function () use ($profiler, $modalUser) {
            $this->prepareListEnvironment();

            return $profiler->measureInteraction(
                $modalUser,
                CandidateList::class,
                fn ($component) => $component->call('openSideMenu', 'add-candidate')
            );
        });

        return $this->finalize($results);
    }

    private function prepareListEnvironment(): void
    {
        config()->set('candidates.mode', 'military');
        Cache::forget(CandidateList::SETTINGS_CACHE_KEY);
        Cache::forget('appeal-statuses:'.app()->getLocale());
    }
}
