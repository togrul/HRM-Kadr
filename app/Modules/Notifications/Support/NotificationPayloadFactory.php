<?php

namespace App\Modules\Notifications\Support;

use App\Models\AttendanceCalendar;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use Illuminate\Support\Carbon;

class NotificationPayloadFactory
{
    public function __construct(
        protected ApprovalRouteResolver $approvalRouteResolver,
    ) {}

    public function birthday(Personnel $personnel): array
    {
        $personnel->loadMissing([
            'position:id,name',
            'structure:id,parent_id,name',
        ]);

        return [
            'type' => 'Birthday',
            'action' => 'birthday',
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'name' => $personnel->fullname,
            'birthdate' => optional($personnel->birthdate)->format('Y-m-d'),
            'birthday_label' => optional($personnel->birthdate)->format('d.m.Y'),
            'position' => $personnel->position?->name,
            'structure' => $personnel->structure?->fullStructureName(),
            'category' => __('notifications::common.categories.birthday'),
            'message' => __('notifications::common.messages.birthday_today'),
        ];
    }

    public function positionChange(Personnel $personnel, array $changes): array
    {
        $personnel->loadMissing([
            'position:id,name',
            'structure:id,parent_id,name',
        ]);

        $oldPosition = ! empty($changes['old_position_id'])
            ? Position::query()->find($changes['old_position_id'])
            : null;
        $oldStructure = ! empty($changes['old_structure_id'])
            ? Structure::query()->find($changes['old_structure_id'])
            : null;

        return [
            'type' => 'PositionChange',
            'action' => 'position_change',
            'personnel_id' => $personnel->id,
            'name' => $personnel->fullname,
            'old_position' => $oldPosition?->name,
            'new_position' => $personnel->position?->name,
            'old_structure' => $oldStructure?->fullStructureName(),
            'new_structure' => $personnel->structure?->fullStructureName(),
            'change_reason' => $changes['reason'] ?? 'Vəzifə yenilənməsi',
            'effective_date' => now()->format('d.m.Y'),
            'category' => __('notifications::common.categories.position_change'),
            'message' => __('notifications::common.messages.position_changed'),
        ];
    }

    public function holiday(AttendanceCalendar $calendar): array
    {
        return [
            'type' => 'Holiday',
            'action' => 'holiday',
            'holiday_name' => $calendar->name ?: __('notifications::common.categories.holiday'),
            'holiday_date' => optional($calendar->date)->format('d.m.Y'),
            'duration' => '1 gün',
            'scope' => $calendar->scope_type === 'structure' ? 'Struktur üzrə' : 'Bütün əməkdaşlar',
            'scope_type' => $calendar->scope_type,
            'structure_id' => $calendar->scope_type === 'structure' ? (int) $calendar->scope_id : null,
            'holiday_rules' => $calendar->is_paid ? 'Ödənişli qeyri-iş günü' : 'Qeyri-iş günü',
            'category' => __('notifications::common.categories.holiday'),
            'message' => __('notifications::common.messages.holiday_due'),
        ];
    }

    public function employmentStarted(Personnel $personnel, array $context = []): array
    {
        $personnel->loadMissing([
            'position:id,name,approval_rank,is_approval_target',
        ]);

        $managerChain = collect($this->approvalRouteResolver->managerChain($personnel))
            ->values();

        $directManager = $managerChain->first();
        $preview = $this->approvalRouteResolver->personnelPreviewCard($personnel);

        return [
            'type' => 'EmploymentStarted',
            'action' => 'employment_started',
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'name' => $personnel->fullname,
            'position' => data_get($preview, 'position'),
            'structure' => data_get($preview, 'structure'),
            'join_work_date' => optional($personnel->join_work_date)->format('Y-m-d'),
            'join_work_date_label' => optional($personnel->join_work_date)->format('d.m.Y'),
            'direct_manager' => data_get($directManager, 'fullname'),
            'manager_chain' => $managerChain->pluck('fullname')->all(),
            'manager_chain_count' => $managerChain->count(),
            'order_no' => data_get($context, 'order_no'),
            'event_source' => data_get($context, 'event_source', 'employment_activation'),
            'category' => __('notifications::common.categories.employment_started'),
            'message' => __('notifications::common.messages.employment_started'),
        ];
    }

    public function manualAnnouncement(array $data): array
    {
        return [
            'type' => 'Announcement',
            'action' => 'announcement',
            'title' => trim((string) $data['title']),
            'name' => trim((string) $data['title']),
            'message' => __('notifications::common.messages.manual_announcement'),
            'body' => trim((string) $data['body']),
            'category' => __('notifications::common.categories.announcement'),
        ];
    }

    public function manualHoliday(array $data): array
    {
        return [
            'type' => 'Holiday',
            'action' => 'holiday',
            'title' => trim((string) ($data['title'] ?? $data['holiday_name'] ?? '')),
            'holiday_name' => trim((string) ($data['holiday_name'] ?? $data['title'] ?? '')),
            'holiday_date' => filled($data['holiday_date'] ?? null)
                ? Carbon::parse((string) $data['holiday_date'])->format('d.m.Y')
                : null,
            'duration' => trim((string) ($data['duration'] ?? '1 gün')),
            'scope' => trim((string) ($data['scope'] ?? __('notifications::common.helpers.all_employees_scope'))),
            'holiday_rules' => trim((string) ($data['holiday_rules'] ?? '')),
            'structure_id' => ($structureIds = $this->parseIntegerList($data['structure_ids'] ?? '')) !== [] ? $structureIds[0] : null,
            'category' => __('notifications::common.categories.holiday'),
            'message' => __('notifications::common.messages.holiday_due'),
            'body' => trim((string) ($data['body'] ?? '')),
        ];
    }

    protected function parseIntegerList(string|array|null $value): array
    {
        return collect(is_array($value) ? $value : explode(',', (string) $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $item) => $item > 0)
            ->values()
            ->all();
    }
}
