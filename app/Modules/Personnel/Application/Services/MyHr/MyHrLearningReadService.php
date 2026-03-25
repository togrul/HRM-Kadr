<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\EmployeeContentAssignment;
use App\Models\Personnel;
use Illuminate\Support\Collection;

class MyHrLearningReadService
{
    public function build(Personnel $personnel): array
    {
        $assignments = EmployeeContentAssignment::query()
            ->where('personnel_id', $personnel->id)
            ->with(['asset', 'view'])
            ->latest('assigned_at')
            ->get();

        return [
            'summary' => $this->summary($assignments),
            'rows' => $assignments->map(fn (EmployeeContentAssignment $assignment) => $this->mapRow($assignment))->all(),
        ];
    }

    protected function summary(Collection $assignments): array
    {
        return [
            'total' => $assignments->count(),
            'pending' => $assignments->filter(fn (EmployeeContentAssignment $assignment) => in_array($this->resolveStatus($assignment), ['assigned', 'opened', 'overdue'], true))->count(),
            'completed' => $assignments->filter(fn (EmployeeContentAssignment $assignment) => $this->resolveStatus($assignment) === 'completed')->count(),
            'required' => $assignments->filter(fn (EmployeeContentAssignment $assignment) => (bool) $assignment->asset?->is_required)->count(),
        ];
    }

    protected function mapRow(EmployeeContentAssignment $assignment): array
    {
        $asset = $assignment->asset;
        $view = $assignment->view;
        $status = $this->resolveStatus($assignment);

        return [
            'id' => $assignment->id,
            'title' => $asset?->title ?: __('personnel::my_hr.learning.messages.missing_asset'),
            'content_type' => $asset?->content_type ?: 'other',
            'content_type_label' => __('personnel::my_hr.learning.content_types.'.($asset?->content_type ?: 'other')),
            'description' => $asset?->description ?: __('personnel::my_hr.learning.messages.no_description'),
            'estimated_minutes' => $asset?->estimated_minutes,
            'status' => $status,
            'status_label' => __('personnel::my_hr.learning.status.'.$status),
            'status_mode' => match ($status) {
                'completed' => 'emerald',
                'overdue' => 'rose',
                'opened' => 'sky',
                default => 'muted',
            },
            'is_required' => (bool) $asset?->is_required,
            'assigned_at' => optional($assignment->assigned_at)->format('d.m.Y H:i') ?: '—',
            'due_at' => optional($assignment->due_at)->format('d.m.Y H:i'),
            'opened_at' => optional($view?->opened_at)->format('d.m.Y H:i'),
            'completed_at' => optional($view?->completed_at)->format('d.m.Y H:i'),
            'watch_progress_percent' => $view?->watch_progress_percent,
            'content_url' => $asset?->contentUrl(),
            'can_complete' => blank($view?->completed_at),
        ];
    }

    protected function resolveStatus(EmployeeContentAssignment $assignment): string
    {
        $view = $assignment->view;

        if ($assignment->status === 'waived') {
            return 'waived';
        }

        if (filled($view?->completed_at)) {
            return 'completed';
        }

        if (filled($assignment->due_at) && $assignment->due_at->isPast()) {
            return 'overdue';
        }

        if (filled($view?->opened_at)) {
            return 'opened';
        }

        return 'assigned';
    }
}
