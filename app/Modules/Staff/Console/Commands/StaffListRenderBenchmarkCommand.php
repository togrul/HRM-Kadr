<?php

namespace App\Modules\Staff\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Modules\Staff\Livewire\Staffs;
use App\Support\Livewire\LivewireComponentProfiler;

class StaffListRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'staff:list-render-benchmark
        {--render-response-budget= : Max response size for staff list render}
        {--render-ms-budget= : Max render time in ms for staff list render}
        {--vacancies-response-budget= : Max response size for vacancies view render}
        {--vacancies-ms-budget= : Max render time in ms for vacancies view render}
        {--modal-response-budget= : Max response size for add staff modal open}
        {--modal-ms-budget= : Max render time in ms for add staff modal open}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Staff list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveUserForPermissions('show-staff');

        if (! $user) {
            $this->error('No user with Staff view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-staff', 'add-staff') ?? $user;

        $budgets = [
            'staffs_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('staff.performance.render_budget.staffs_render.response_bytes', 260000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('staff.performance.render_budget.staffs_render.render_ms', 220))),
            ],
            'staffs_vacancies_render' => [
                'response_bytes' => max(1, (int) ($this->option('vacancies-response-budget') ?: config('staff.performance.render_budget.staffs_vacancies_render.response_bytes', 220000))),
                'render_ms' => max(1, (float) ($this->option('vacancies-ms-budget') ?: config('staff.performance.render_budget.staffs_vacancies_render.render_ms', 220))),
            ],
            'staffs_add_modal_open' => [
                'response_bytes' => max(1, (int) ($this->option('modal-response-budget') ?: config('staff.performance.render_budget.staffs_add_modal_open.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('modal-ms-budget') ?: config('staff.performance.render_budget.staffs_add_modal_open.render_ms', 160))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('staffs_render', $budgets['staffs_render'], fn () => $profiler->measureRender($user, Staffs::class));
        $results[] = $this->probe('staffs_vacancies_render', $budgets['staffs_vacancies_render'], fn () => $profiler->measureInteraction($user, Staffs::class, fn ($component) => $component->call('showPage', 'vacancies')));
        $results[] = $this->probe('staffs_add_modal_open', $budgets['staffs_add_modal_open'], fn () => $profiler->measureInteraction($modalUser, Staffs::class, fn ($component) => $component->call('openSideMenu', 'add-staff')));

        return $this->finalize($results);
    }
}
