<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use App\Models\User;
use App\Modules\Reports\Application\Jobs\SendScheduledReportJob;
use App\Modules\Reports\Application\Pdf\ReportPdfRenderer;
use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportScheduleService;
use App\Modules\Reports\Application\Services\ReportSelectionService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Application\Services\StandardReportCatalogService;
use App\Modules\Reports\Domain\Models\ReportSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Isolate;
use Livewire\Component;

/**
 * Planlı hesabatlar: istifadəçinin öz planlarının siyahısı və yaratma/dəyişmə forması.
 * Biznes məntiqi ReportScheduleService-dədir; komponent yalnız formu ötürür.
 */
#[Isolate]
class Schedules extends Component
{
    use ConfirmsDestructiveActions;

    public ?int $editingId = null;

    public string $name = '';

    public string $tab = ReportSelectionService::TAB_STANDARD;

    public string $report = 'headcount';

    public string $source = 'personnel';

    public string $groupBy = 'structure';

    public string $metric = 'count';

    public ?int $structureId = null;

    public string $period = ReportSchedule::PERIOD_PREVIOUS_MONTH;

    public string $frequency = ReportSchedule::FREQUENCY_MONTHLY;

    public int $weekday = 1;

    public int $monthDay = 1;

    public string $sendTime = '08:00';

    public string $format = ReportSchedule::FORMAT_XLSX;

    /**
     * @var list<int>
     */
    public array $recipientIds = [];

    public string $recipientSearch = '';

    /**
     * @var array<int,array<string,mixed>>
     */
    public array $reportOptions = [];

    /**
     * @var array<int,array<string,mixed>>
     */
    public array $sourceOptions = [];

    /**
     * @var array<int,array{key:string,label:string}>
     */
    public array $groupOptions = [];

    /**
     * @var array<int,array{key:string,label:string}>
     */
    public array $metricOptions = [];

    /**
     * @var array<int,array{id:int,label:string}>
     */
    public array $structureOptions = [];

    public bool $pdfAvailable = true;

    public function mount(
        ReportsAccessService $access,
        StandardReportCatalogService $catalog,
        DynamicReportBuilderService $builder,
        ReportsStructureScopeService $structures,
        ReportPdfRenderer $pdf,
    ): void {
        $this->authorize('viewAny', ReportSchedule::class);

        $this->reportOptions = $access->filterStandardReports($catalog->all());
        $this->sourceOptions = $access->filterDynamicSources($builder->sourceOptions());
        $this->structureOptions = $structures->filterOptions()->all();
        $this->pdfAvailable = $pdf->isAvailable();
        $this->resetForm();
    }

    public function updatedSource(): void
    {
        $this->syncDependentOptions();
    }

    public function updatedTab(): void
    {
        $this->tab = in_array($this->tab, ReportSelectionService::TABS, true) ? $this->tab : ReportSelectionService::TAB_STANDARD;
        $this->syncDependentOptions();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function schedules(): array
    {
        $reportLabels = array_column($this->reportOptions, 'label', 'key');
        $sourceLabels = array_column($this->sourceOptions, 'label', 'key');

        return app(ReportScheduleService::class)->listFor($this->currentUser())
            ->map(fn (ReportSchedule $schedule): array => [
                'id' => (int) $schedule->id,
                'name' => (string) $schedule->name,
                'report' => $schedule->tab === ReportSelectionService::TAB_DYNAMIC
                    ? (string) ($sourceLabels[$schedule->filters['source'] ?? ''] ?? ($schedule->filters['source'] ?? ''))
                    : (string) ($reportLabels[$schedule->filters['report'] ?? ''] ?? ($schedule->filters['report'] ?? '')),
                'frequency' => __('reports::schedules.options.frequencies.'.$schedule->frequency),
                'format' => __('reports::schedules.options.formats.'.$schedule->format),
                'recipients' => (int) ($schedule->getAttribute('recipients_count') ?? 0),
                'active' => (bool) $schedule->is_active,
                'next_run' => $schedule->is_active ? $schedule->next_run_at?->format('d.m.Y H:i') : null,
                'last_status' => $schedule->last_status,
                'last_error' => $schedule->last_error,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    public function recipientCandidates(): array
    {
        return array_values(array_filter(
            app(ReportScheduleService::class)->recipientCandidates($this->recipientSearch),
            fn (array $candidate): bool => ! in_array($candidate['id'], $this->recipientIds, true)
        ));
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    public function selectedRecipients(): array
    {
        if ($this->recipientIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $this->recipientIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => ['id' => (int) $user->id, 'name' => (string) $user->name, 'email' => (string) $user->email])
            ->all();
    }

    public function addRecipient(int $userId): void
    {
        if (! in_array($userId, $this->recipientIds, true)) {
            $this->recipientIds[] = $userId;
        }

        $this->recipientSearch = '';
    }

    public function removeRecipient(int $userId): void
    {
        $this->recipientIds = array_values(array_filter($this->recipientIds, fn (int $id): bool => $id !== $userId));
    }

    public function save(ReportScheduleService $service): void
    {
        $schedule = $this->editingId !== null ? ReportSchedule::query()->findOrFail($this->editingId) : null;
        $this->resetErrorBag();

        try {
            $service->save($this->currentUser(), [
                'name' => $this->name,
                'tab' => $this->tab,
                'filters' => $this->tab === ReportSelectionService::TAB_DYNAMIC
                    ? ['source' => $this->source, 'group_by' => $this->groupBy, 'metric' => $this->metric, 'structure_id' => $this->structureId]
                    : ['report' => $this->report, 'structure_id' => $this->structureId],
                'period' => $this->period,
                'frequency' => $this->frequency,
                'weekday' => $this->weekday,
                'month_day' => $this->monthDay,
                'send_time' => $this->sendTime,
                'format' => $this->format,
                'recipient_ids' => $this->recipientIds,
                'is_active' => $schedule === null ? true : $schedule->is_active,
            ], $schedule);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError($this->errorField((string) $key), (string) ($messages[0] ?? ''));
            }

            return;
        }

        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('reports::schedules.messages.saved'));
    }

    public function edit(int $scheduleId): void
    {
        $schedule = ReportSchedule::query()->findOrFail($scheduleId);
        $this->authorize('update', $schedule);

        $filters = $schedule->filters;
        $this->editingId = (int) $schedule->id;
        $this->name = (string) $schedule->name;
        $this->tab = (string) $schedule->tab;
        $this->report = (string) ($filters['report'] ?? $this->report);
        $this->source = (string) ($filters['source'] ?? $this->source);
        $this->syncDependentOptions();
        $this->groupBy = (string) ($filters['group_by'] ?? $this->groupBy);
        $this->metric = (string) ($filters['metric'] ?? $this->metric);
        $this->structureId = isset($filters['structure_id']) ? (int) $filters['structure_id'] : null;
        $this->period = (string) $schedule->period;
        $this->frequency = (string) $schedule->frequency;
        $this->weekday = (int) ($schedule->weekday ?? 1);
        $this->monthDay = (int) ($schedule->month_day ?? 1);
        $this->sendTime = (string) $schedule->send_time;
        $this->format = (string) $schedule->format;
        $this->recipientIds = $schedule->recipients()->pluck('users.id')->map(fn ($id): int => (int) $id)->all();
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggle(int $scheduleId, ReportScheduleService $service): void
    {
        $schedule = ReportSchedule::query()->findOrFail($scheduleId);
        $schedule = $service->setActive($this->currentUser(), $schedule, ! $schedule->is_active);

        $this->dispatch('notify', type: 'success', message: $schedule->is_active
            ? __('reports::schedules.messages.activated')
            : __('reports::schedules.messages.deactivated'));
    }

    public function sendNow(int $scheduleId): void
    {
        $schedule = ReportSchedule::query()->findOrFail($scheduleId);
        $this->authorize('update', $schedule);

        SendScheduledReportJob::dispatch((int) $schedule->id);

        $schedule->refresh();
        if ($schedule->last_status === ReportSchedule::STATUS_FAILED && config('queue.default') === 'sync') {
            $this->dispatch('notify', type: 'error', message: __('reports::schedules.messages.send_failed', ['reason' => (string) $schedule->last_error]));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('reports::schedules.messages.sent'));
    }

    public function confirmScheduleDeletion(int $scheduleId): void
    {
        $schedule = ReportSchedule::query()->findOrFail($scheduleId);
        $this->authorize('delete', $schedule);

        $this->confirmDeletion(
            action: 'deleteSchedule',
            parameters: [$scheduleId],
            message: __('reports::schedules.confirmations.delete'),
            description: (string) $schedule->name,
        );
    }

    public function deleteSchedule(int $scheduleId, ReportScheduleService $service): void
    {
        $service->delete($this->currentUser(), ReportSchedule::query()->findOrFail($scheduleId));

        if ($this->editingId === $scheduleId) {
            $this->resetForm();
        }

        $this->dispatch('notify', type: 'success', message: __('reports::schedules.messages.deleted'));
    }

    public function render(): View
    {
        return view('reports::livewire.reports.schedules');
    }

    public function placeholder(): View
    {
        return view('reports::livewire.reports.placeholder');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->tab = $this->reportOptions !== [] ? ReportSelectionService::TAB_STANDARD : ReportSelectionService::TAB_DYNAMIC;
        $this->report = (string) ($this->reportOptions[0]['key'] ?? 'headcount');
        $this->source = (string) ($this->sourceOptions[0]['key'] ?? 'personnel');
        $this->structureId = null;
        $this->period = ReportSchedule::PERIOD_PREVIOUS_MONTH;
        $this->frequency = ReportSchedule::FREQUENCY_MONTHLY;
        $this->weekday = 1;
        $this->monthDay = 1;
        $this->sendTime = '08:00';
        $this->format = ReportSchedule::FORMAT_XLSX;
        $this->recipientIds = [];
        $this->recipientSearch = '';
        $this->syncDependentOptions();
        $this->resetErrorBag();
    }

    protected function syncDependentOptions(): void
    {
        $builder = app(DynamicReportBuilderService::class);
        $this->groupOptions = $builder->groupOptions($this->source);
        $this->metricOptions = $builder->metricOptions($this->source);

        $groupKeys = array_column($this->groupOptions, 'key');
        if (! in_array($this->groupBy, $groupKeys, true)) {
            $this->groupBy = (string) ($groupKeys[0] ?? 'structure');
        }

        $metricKeys = array_column($this->metricOptions, 'key');
        if (! in_array($this->metric, $metricKeys, true)) {
            $this->metric = (string) ($metricKeys[0] ?? 'count');
        }
    }

    /**
     * Servis xətası açarlarını formun sahələrinə uyğunlaşdırır.
     */
    protected function errorField(string $key): string
    {
        return match ($key) {
            'structure_id' => 'structureId',
            'send_time' => 'sendTime',
            'month_day' => 'monthDay',
            'recipient_ids' => 'recipientIds',
            'group_by' => 'groupBy',
            default => $key,
        };
    }

    protected function currentUser(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
