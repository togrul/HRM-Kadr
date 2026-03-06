<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceSetting;

class AttendanceSettingsService
{
    public function getGlobal(): AttendanceSetting
    {
        return AttendanceSetting::query()->firstOrCreate(
            [
                'scope_type' => 'global',
                'scope_id' => null,
            ],
            [
                'timezone' => 'Asia/Baku',
                'late_grace_minutes' => 0,
                'early_leave_grace_minutes' => 0,
                'rounding_policy' => (string) config('attendance.processing.policy_defaults.rounding_policy', 'none'),
                'rounding_step_minutes' => (int) config('attendance.processing.policy_defaults.rounding_step_minutes', 5),
                'overtime_policy' => (string) config('attendance.processing.policy_defaults.overtime_policy', 'by_approval'),
                'is_active' => true,
            ]
        );
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function updateGlobal(array $payload, int $userId): AttendanceSetting
    {
        $setting = $this->getGlobal();

        $before = $setting->only([
            'timezone',
            'default_shift_id',
            'late_grace_minutes',
            'early_leave_grace_minutes',
            'rounding_policy',
            'rounding_step_minutes',
            'overtime_policy',
            'is_active',
        ]);

        $setting->timezone = (string) ($payload['timezone'] ?? $setting->timezone);
        $setting->default_shift_id = ! empty($payload['default_shift_id'])
            ? (int) $payload['default_shift_id']
            : null;
        $setting->late_grace_minutes = max(0, (int) ($payload['late_grace_minutes'] ?? 0));
        $setting->early_leave_grace_minutes = max(0, (int) ($payload['early_leave_grace_minutes'] ?? 0));
        $setting->rounding_policy = (string) ($payload['rounding_policy'] ?? 'none');
        $setting->rounding_step_minutes = max(1, (int) ($payload['rounding_step_minutes'] ?? 5));
        $setting->overtime_policy = (string) ($payload['overtime_policy'] ?? 'by_approval');
        $setting->is_active = true;
        $setting->updated_by = $userId;
        if (! $setting->created_by) {
            $setting->created_by = $userId;
        }

        $setting->save();

        app(AttendanceAuditLogger::class)->log(
            event: 'settings.updated',
            description: 'Attendance settings updated.',
            subject: $setting,
            properties: [
                'before' => $before,
                'after' => $setting->only([
                    'timezone',
                    'default_shift_id',
                    'late_grace_minutes',
                    'early_leave_grace_minutes',
                    'rounding_policy',
                    'rounding_step_minutes',
                    'overtime_policy',
                    'is_active',
                ]),
            ],
            causerId: $userId
        );

        app(AttendanceCacheService::class)->forgetOverviewMonth(
            year: (int) now()->year,
            month: (int) now()->month
        );

        return $setting->refresh();
    }
}
