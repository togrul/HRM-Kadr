<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingSessionParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MyHrDevelopmentPlanReadService
{
    public function build(Personnel $personnel, array $filters = []): array
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        $statusFilter = (string) ($filters['status'] ?? 'all');

        $items = TrainingNeedItem::query()
            ->where('personnel_id', $personnel->id)
            ->with([
                'competency:id,name,training_competency_group_id',
                'competency.group:id,name',
                'recommendedProgram:id,title,code,duration_hours',
                'targetLevel:id,name',
                'sessionParticipants' => fn ($query) => $query
                    ->where('personnel_id', $personnel->id)
                    ->with([
                        'session:id,title,training_program_id,scheduled_start_at,scheduled_end_at,location,status,completed_at',
                        'session.program:id,title,code',
                    ]),
            ])
            ->orderByRaw('target_completion_date is null')
            ->orderBy('target_completion_date')
            ->latest('id')
            ->get();

        $rows = $items
            ->map(fn (TrainingNeedItem $item): array => $this->mapRow($item))
            ->filter(function (array $row) use ($search, $statusFilter): bool {
                if ($statusFilter !== 'all' && $row['status_key'] !== $statusFilter) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                return str_contains($row['search_blob'], $search);
            })
            ->values();

        return [
            'summary' => $this->summary($items),
            'rows' => $rows
                ->map(function (array $row): array {
                    unset($row['search_blob']);

                    return $row;
                })
                ->all(),
        ];
    }

    protected function summary(Collection $items): array
    {
        $participants = $items
            ->flatMap(fn (TrainingNeedItem $item) => $item->sessionParticipants)
            ->values();

        return [
            'total' => $items->count(),
            'planned' => $participants
                ->filter(fn (TrainingSessionParticipant $participant): bool => in_array($participant->attendance_status, ['planned', 'confirmed'], true))
                ->unique('training_session_id')
                ->count(),
            'completed' => $participants
                ->filter(fn (TrainingSessionParticipant $participant): bool => $participant->attendance_status === 'attended')
                ->unique('training_session_id')
                ->count(),
            'needs_completed' => $items->filter(fn (TrainingNeedItem $item): bool => (string) $item->status === 'completed')->count(),
        ];
    }

    protected function mapRow(TrainingNeedItem $item): array
    {
        $status = (string) ($item->status ?: 'draft');
        $priority = (string) ($item->priority ?: 'medium');
        $reason = trim((string) $item->presentedReason());
        $planNote = trim((string) $item->presentedPlanNote());
        $sessions = $item->sessionParticipants
            ->sortBy(function (TrainingSessionParticipant $participant): int {
                return optional($participant->session?->scheduled_start_at)->timestamp ?? PHP_INT_MAX;
            })
            ->values()
            ->map(fn (TrainingSessionParticipant $participant): array => $this->mapSession($participant))
            ->all();

        return [
            'id' => $item->id,
            'title' => $item->competency?->name ?: __('training_needs::dashboard.labels.no_competency'),
            'status_key' => $status,
            'status_label' => __('training_needs::dashboard.need_statuses.'.$status),
            'status_mode' => $this->statusMode($status),
            'priority_label' => __('personnel::my_hr.development_plan.priority.'.$priority),
            'priority_mode' => $this->priorityMode($priority),
            'source_label' => __('training_needs::dashboard.sources.'.((string) ($item->source ?: 'manual'))),
            'target_date_badge' => optional($item->target_completion_date)->format('d.m.Y') ?: __('personnel::my_hr.development_plan.messages.no_target_date'),
            'summary' => $reason !== '' ? $reason : __('personnel::my_hr.development_plan.messages.no_reason'),
            'plan_note' => $planNote !== '' ? $planNote : null,
            'details' => [
                ['label' => __('personnel::my_hr.development_plan.labels.group'), 'value' => $item->competency?->group?->name ?: __('training_needs::dashboard.labels.no_group')],
                ['label' => __('personnel::my_hr.development_plan.labels.recommended_program'), 'value' => $item->recommendedProgram?->title ?: __('personnel::my_hr.development_plan.messages.no_program')],
                ['label' => __('personnel::my_hr.development_plan.labels.target_level'), 'value' => $item->targetLevel?->name ?: __('training_needs::dashboard.labels.no_target_level')],
                ['label' => __('personnel::my_hr.development_plan.labels.target_completion_date'), 'value' => optional($item->target_completion_date)->format('d.m.Y') ?: __('personnel::my_hr.development_plan.messages.no_target_date')],
            ],
            'sessions' => $sessions,
            'search_blob' => Str::lower(implode(' ', array_filter([
                $item->competency?->name,
                $item->competency?->group?->name,
                $item->recommendedProgram?->title,
                $reason,
                $planNote,
                __('training_needs::dashboard.need_statuses.'.$status),
                __('training_needs::dashboard.sources.'.((string) ($item->source ?: 'manual'))),
            ]))),
        ];
    }

    protected function mapSession(TrainingSessionParticipant $participant): array
    {
        $session = $participant->session;
        $attendanceStatus = (string) ($participant->attendance_status ?: 'planned');
        $sessionStatus = (string) ($session?->status ?: 'scheduled');

        return [
            'title' => $session?->title ?: __('training_needs::dashboard.labels.default_session_title'),
            'program' => $session?->program?->title,
            'window' => trim(implode(' – ', array_filter([
                optional($session?->scheduled_start_at)->format('d.m.Y H:i'),
                optional($session?->scheduled_end_at)->format('d.m.Y H:i'),
            ]))) ?: '—',
            'location' => $session?->location ?: '—',
            'attendance_status_label' => __('training_needs::dashboard.attendance_statuses.'.$attendanceStatus),
            'attendance_status_mode' => $this->attendanceMode($attendanceStatus),
            'session_status_label' => __('training_needs::dashboard.session_statuses.'.$sessionStatus),
            'session_status_mode' => $this->sessionMode($sessionStatus),
        ];
    }

    protected function statusMode(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'planned', 'approved' => 'info',
            'review' => 'warning',
            default => 'neutral',
        };
    }

    protected function priorityMode(string $priority): string
    {
        return match ($priority) {
            'high' => 'danger',
            'medium' => 'warning',
            default => 'neutral',
        };
    }

    protected function attendanceMode(string $status): string
    {
        return match ($status) {
            'attended' => 'success',
            'confirmed' => 'info',
            'absent', 'cancelled' => 'danger',
            default => 'neutral',
        };
    }

    protected function sessionMode(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'in_progress' => 'info',
            'cancelled' => 'danger',
            default => 'neutral',
        };
    }
}
