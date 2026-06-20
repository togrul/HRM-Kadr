<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceCalendarManagementService
{
    /**
     * @param  array<string,mixed>  $payload
     * @throws ValidationException
     */
    public function upsert(array $payload, int $userId, ?AttendanceCalendar $calendar = null): AttendanceCalendar
    {
        $calendar ??= new AttendanceCalendar();

        $scopeType = (string) ($payload['scope_type'] ?? 'global');
        $scopeId = $scopeType === 'structure' && is_numeric($payload['scope_id'] ?? null)
            ? (int) $payload['scope_id']
            : null;
        $date = Carbon::parse((string) ($payload['date'] ?? now()->toDateString()))->toDateString();

        $this->guardUniqueScopeDate(
            scopeType: $scopeType,
            scopeId: $scopeId,
            date: $date,
            ignoreId: $calendar->exists ? (int) $calendar->id : null
        );

        $before = $calendar->exists ? $calendar->only([
            'date',
            'day_type',
            'name',
            'is_paid',
            'scope_type',
            'scope_id',
        ]) : [];

        $after = [
            'date' => $date,
            'day_type' => (string) ($payload['day_type'] ?? 'workday'),
            'name' => filled($payload['name'] ?? null) ? trim((string) $payload['name']) : null,
            'is_paid' => (bool) ($payload['is_paid'] ?? true),
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ];

        if ($calendar->exists && $this->normalizeComparableState($before) === $this->normalizeComparableState($after)) {
            return $calendar->refresh();
        }

        $calendar->date = $after['date'];
        $calendar->day_type = $after['day_type'];
        $calendar->name = $after['name'];
        $calendar->is_paid = $after['is_paid'];
        $calendar->scope_type = $after['scope_type'];
        $calendar->scope_id = $after['scope_id'];
        $calendar->updated_by = $userId;

        if (! $calendar->exists) {
            $calendar->created_by = $userId;
        }

        $calendar->save();

        app(AttendanceAuditLogger::class)->log(
            event: $calendar->wasRecentlyCreated ? 'calendar.created' : 'calendar.updated',
            description: $calendar->wasRecentlyCreated ? 'Attendance calendar created.' : 'Attendance calendar updated.',
            subject: $calendar,
            properties: [
                'before' => $before,
                'after' => $after,
            ],
            causerId: $userId
        );

        app(AttendanceCalendarSyncService::class)->syncCalendarChange(
            calendar: $calendar,
            original: $before
        );

        return $calendar->refresh();
    }

    public function delete(AttendanceCalendar $calendar, int $userId): void
    {
        $before = $calendar->only([
            'date',
            'day_type',
            'name',
            'is_paid',
            'scope_type',
            'scope_id',
        ]);

        $calendar->delete();

        app(AttendanceAuditLogger::class)->log(
            event: 'calendar.deleted',
            description: 'Attendance calendar deleted.',
            subject: null,
            properties: ['before' => $before],
            causerId: $userId
        );

        app(AttendanceCalendarSyncService::class)->syncCalendarChange(
            calendar: null,
            original: $before
        );
    }

    /**
     * @throws ValidationException
     */
    private function guardUniqueScopeDate(string $scopeType, ?int $scopeId, string $date, ?int $ignoreId = null): void
    {
        $exists = AttendanceCalendar::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('scope_type', $scopeType)
            ->whereDate('date', $date)
            ->where(function ($query) use ($scopeId): void {
                if ($scopeId === null) {
                    $query->whereNull('scope_id');

                    return;
                }

                $query->where('scope_id', $scopeId);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'form.date' => __('attendance::calendar_regimes.messages.duplicate_scope_date'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function normalizeComparableState(array $state): array
    {
        return [
            'date' => filled($state['date'] ?? null)
                ? Carbon::parse((string) $state['date'])->toDateString()
                : null,
            'day_type' => (string) ($state['day_type'] ?? 'workday'),
            'name' => filled($state['name'] ?? null) ? trim((string) $state['name']) : null,
            'is_paid' => (bool) ($state['is_paid'] ?? true),
            'scope_type' => (string) ($state['scope_type'] ?? 'global'),
            'scope_id' => is_numeric($state['scope_id'] ?? null) ? (int) $state['scope_id'] : null,
        ];
    }
}
