<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use App\Modules\EmployeeLifecycle\Contracts\OrderLifecycleSync;
use App\Services\Modules\ModuleState;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Köçürmə və xitam əmrlərindən həyat dövrü hadisələri yaradır (işə qəbul inteqrasiyasının
 * — CandidateHireConversionService::ensureOrderLifecycleForCandidate() — eyni naxışı):
 *
 *  - köçürmə → `movement` hadisəsi (source_type `order_transfer`), tamamlanmış yerdəyişmə qeydi
 *    və «rol dəyişikliyi» checklist-i (aktiv `movement` plan şablonundan, yoxdursa standart);
 *  - xitam → `offboarding` hadisəsi (source_type `order_termination`), açıq işdən ayrılma işi və
 *    checklist (aktiv `offboarding` şablonu və ya standart) + həmişə son haqq-hesab tapşırığı.
 *
 * Açar (source_type, source_id = əmr id, personnel_id) olduğundan təkrar çağırış idempotentdir;
 * ləğv olunmuş hadisə yenidən təsdiqdə təzədən açılır. Çağırışlar əmr effektinin tranzaksiyası
 * daxilindədir — effekt geri qayıdarsa, hadisə də yaranmır.
 */
class OrderLifecycleEventService implements OrderLifecycleSync
{
    private const CLOSED = ['completed', 'cancelled'];

    public function __construct(private readonly ModuleState $modules) {}

    public function transferApplied(int $orderId, ?string $orderNo, int $personnelId, ?string $effectiveDate, array $movement): void
    {
        if (! $this->available('employee_lifecycle_movements')) {
            return;
        }

        $personnel = DB::table('personnels')->where('id', $personnelId)->first(['id', 'tabel_no']);
        if ($personnel === null) {
            return;
        }

        $effective = Carbon::parse($effectiveDate ?: today());
        $existing = $this->existingEvent(self::SOURCE_TRANSFER, $orderId, $personnelId);

        if ($existing !== null) {
            if ($existing->status === 'cancelled') {
                $this->reopen((int) $existing->id, 'in_progress');
                DB::table('employee_lifecycle_movements')->where('event_id', $existing->id)->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        $template = $this->activeTemplate('movement');
        $deadline = $effective->copy()->addDays($template !== null ? (int) $template->default_duration_days : 7);
        $meta = [
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'source' => 'transfer_order',
            'movement_type' => 'transfer',
            'current_structure_id' => $movement['from_structure_id'] ?? null,
            'current_position_id' => $movement['from_position_id'] ?? null,
            'target_structure_id' => $movement['to_structure_id'] ?? null,
            'target_position_id' => $movement['to_position_id'] ?? null,
            'template_id' => $template !== null ? (int) $template->id : null,
        ];

        $eventId = $this->insertEvent($personnel, 'movement', self::SOURCE_TRANSFER, $orderId, __('employee-lifecycle::dashboard.event_titles.order_transfer'), __('employee-lifecycle::dashboard.order_events.transfer_description', ['order' => $orderNo ?: '#'.$orderId]), $effective, $deadline, $meta, $template);

        DB::table('employee_lifecycle_movements')->insert([
            'event_id' => $eventId,
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'movement_type' => 'transfer',
            'current_structure_id' => $movement['from_structure_id'] ?? null,
            'current_position_id' => $movement['from_position_id'] ?? null,
            'target_structure_id' => $movement['to_structure_id'] ?? null,
            'target_position_id' => $movement['to_position_id'] ?? null,
            'effective_date' => $effective->toDateString(),
            'status' => 'completed',
            'reason' => __('employee-lifecycle::dashboard.order_events.transfer_description', ['order' => $orderNo ?: '#'.$orderId]),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'completed_at' => now(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertTasks($eventId, $effective, $template, $this->defaultTransferChecklist());
    }

    public function terminationApplied(int $orderId, ?string $orderNo, int $personnelId, string $lastWorkingDate, ?string $reason = null): void
    {
        if (! $this->available('employee_lifecycle_offboarding_cases')) {
            return;
        }

        $personnel = DB::table('personnels')->where('id', $personnelId)->first(['id', 'tabel_no']);
        if ($personnel === null) {
            return;
        }

        $lastDay = Carbon::parse($lastWorkingDate);
        $existing = $this->existingEvent(self::SOURCE_TERMINATION, $orderId, $personnelId);

        if ($existing !== null) {
            if ($existing->status === 'cancelled') {
                $this->reopen((int) $existing->id, 'in_progress');
                DB::table('employee_lifecycle_offboarding_cases')->where('event_id', $existing->id)->update([
                    'status' => 'open',
                    'completed_at' => null,
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        $template = $this->activeTemplate('offboarding');
        $deadline = $lastDay->copy()->addDays($template !== null ? (int) $template->default_duration_days : 10);
        $description = __('employee-lifecycle::dashboard.order_events.termination_description', ['order' => $orderNo ?: '#'.$orderId]);
        $meta = [
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'source' => 'termination_order',
            'template_id' => $template !== null ? (int) $template->id : null,
        ];

        $eventId = $this->insertEvent($personnel, 'offboarding', self::SOURCE_TERMINATION, $orderId, __('employee-lifecycle::dashboard.event_titles.order_termination'), $reason ?: $description, $lastDay, $deadline, $meta, $template);

        DB::table('employee_lifecycle_offboarding_cases')->insert([
            'event_id' => $eventId,
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'last_working_date' => $lastDay->toDateString(),
            'status' => 'open',
            'reason' => mb_substr($reason ?: $description, 0, 255),
            'owner_user_id' => auth()->id(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tasks = $this->defaultTerminationChecklist();
        $sortOrder = $this->insertTasks($eventId, $lastDay, $template, $tasks);

        // Son haqq-hesab əmrin qanuni nəticəsidir (ƏM m.77): şablon nə olursa olsun tapşırıq yaranır.
        if ($template !== null) {
            $this->insertTask($eventId, $lastDay, [
                'title' => __('employee-lifecycle::dashboard.checklist.final_settlement'),
                'owner_type' => 'hr',
                'due_offset_days' => 0,
            ], $sortOrder + 10, null);
        }
    }

    public function orderReversed(int $orderId, int $personnelId, string $kind): void
    {
        if (! $this->available()) {
            return;
        }

        $sourceType = $kind === 'termination' ? self::SOURCE_TERMINATION : self::SOURCE_TRANSFER;
        $event = $this->existingEvent($sourceType, $orderId, $personnelId);

        if ($event === null || $event->status === 'cancelled') {
            return;
        }

        DB::table('employee_lifecycle_events')->where('id', $event->id)->update([
            'status' => 'cancelled',
            'completed_at' => null,
            'updated_at' => now(),
        ]);

        DB::table('employee_lifecycle_tasks')
            ->where('event_id', $event->id)
            ->whereNotIn('status', self::CLOSED)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        $table = $kind === 'termination' ? 'employee_lifecycle_offboarding_cases' : 'employee_lifecycle_movements';
        if (InstalledTables::has($table)) {
            DB::table($table)->where('event_id', $event->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        }
    }

    private function available(?string $extraTable = null): bool
    {
        if (! $this->modules->enabled('employee-lifecycle')) {
            return false;
        }

        return InstalledTables::has('employee_lifecycle_events')
            && InstalledTables::has('employee_lifecycle_tasks')
            && ($extraTable === null || InstalledTables::has($extraTable));
    }

    private function existingEvent(string $sourceType, int $orderId, int $personnelId): ?object
    {
        return DB::table('employee_lifecycle_events')
            ->where('source_type', $sourceType)
            ->where('source_id', $orderId)
            ->where('personnel_id', $personnelId)
            ->lockForUpdate()
            ->first(['id', 'status']);
    }

    private function reopen(int $eventId, string $status): void
    {
        DB::table('employee_lifecycle_events')->where('id', $eventId)->update([
            'status' => $status,
            'completed_at' => null,
            'updated_at' => now(),
        ]);

        DB::table('employee_lifecycle_tasks')
            ->where('event_id', $eventId)
            ->where('status', 'cancelled')
            ->update(['status' => 'open', 'updated_at' => now()]);
    }

    private function activeTemplate(string $type): ?object
    {
        if (! InstalledTables::has('employee_lifecycle_plan_templates') || ! InstalledTables::has('employee_lifecycle_task_templates')) {
            return null;
        }

        return DB::table('employee_lifecycle_plan_templates')
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('id')
            ->first(['id', 'name', 'default_duration_days']);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function insertEvent(object $personnel, string $type, string $sourceType, int $orderId, string $title, ?string $description, Carbon $effective, Carbon $deadline, array $meta, ?object $template): int
    {
        $row = [
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => $type,
            'status' => 'in_progress',
            'title' => $title,
            'description' => $description,
            'effective_date' => $effective->toDateString(),
            'deadline_at' => $deadline->toDateString(),
            'owner_user_id' => auth()->id(),
            'source_type' => $sourceType,
            'source_id' => $orderId,
            'meta' => json_encode($meta),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($template !== null && InstalledTables::hasColumn('employee_lifecycle_events', 'plan_template_id')) {
            $row['plan_template_id'] = (int) $template->id;
        }

        return (int) DB::table('employee_lifecycle_events')->insertGetId($row);
    }

    /**
     * Şablon varsa onun tapşırıqları, yoxdursa standart checklist. Son sort_order qaytarılır.
     *
     * @param  list<array{title: string, owner_type: string, due_offset_days: int}>  $defaults
     */
    private function insertTasks(int $eventId, Carbon $start, ?object $template, array $defaults): int
    {
        $sortOrder = 0;

        if ($template !== null) {
            $taskTemplates = DB::table('employee_lifecycle_task_templates')
                ->where('plan_template_id', $template->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'title', 'owner_type', 'due_offset_days', 'sort_order']);

            if ($taskTemplates->isNotEmpty()) {
                foreach ($taskTemplates as $taskTemplate) {
                    $sortOrder = (int) $taskTemplate->sort_order;
                    $this->insertTask($eventId, $start, [
                        'title' => (string) $taskTemplate->title,
                        'owner_type' => (string) $taskTemplate->owner_type,
                        'due_offset_days' => (int) $taskTemplate->due_offset_days,
                    ], $sortOrder, (int) $taskTemplate->id);
                }

                return $sortOrder;
            }
        }

        foreach ($defaults as $index => $task) {
            $sortOrder = ($index + 1) * 10;
            $this->insertTask($eventId, $start, $task, $sortOrder, null);
        }

        return $sortOrder;
    }

    /**
     * @param  array{title: string, owner_type: string, due_offset_days: int}  $task
     */
    private function insertTask(int $eventId, Carbon $start, array $task, int $sortOrder, ?int $taskTemplateId): void
    {
        $row = [
            'event_id' => $eventId,
            'title' => $task['title'],
            'owner_type' => $task['owner_type'],
            'owner_user_id' => auth()->id(),
            'due_at' => $start->copy()->addDays($task['due_offset_days'])->toDateString(),
            'status' => 'open',
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($taskTemplateId !== null && InstalledTables::hasColumn('employee_lifecycle_tasks', 'task_template_id')) {
            $row['task_template_id'] = $taskTemplateId;
        }

        DB::table('employee_lifecycle_tasks')->insert($row);
    }

    /**
     * @return list<array{title: string, owner_type: string, due_offset_days: int}>
     */
    private function defaultTransferChecklist(): array
    {
        return [
            ['title' => __('employee-lifecycle::dashboard.checklist.update_access_rights'), 'owner_type' => 'it', 'due_offset_days' => 1],
            ['title' => __('employee-lifecycle::dashboard.checklist.reassign_equipment'), 'owner_type' => 'it', 'due_offset_days' => 3],
            ['title' => __('employee-lifecycle::dashboard.checklist.handover_previous_role'), 'owner_type' => 'manager', 'due_offset_days' => 3],
            ['title' => __('employee-lifecycle::dashboard.checklist.agree_new_role_goals'), 'owner_type' => 'manager', 'due_offset_days' => 7],
        ];
    }

    /**
     * @return list<array{title: string, owner_type: string, due_offset_days: int}>
     */
    private function defaultTerminationChecklist(): array
    {
        return [
            ['title' => __('employee-lifecycle::dashboard.checklist.verify_handover_assets'), 'owner_type' => 'manager', 'due_offset_days' => 0],
            ['title' => __('employee-lifecycle::dashboard.checklist.close_system_access'), 'owner_type' => 'it', 'due_offset_days' => 0],
            ['title' => __('employee-lifecycle::dashboard.checklist.final_settlement'), 'owner_type' => 'hr', 'due_offset_days' => 0],
            ['title' => __('employee-lifecycle::dashboard.checklist.complete_exit_interview'), 'owner_type' => 'hr', 'due_offset_days' => 3],
        ];
    }
}
