<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\OnboardingDocumentAssignment;
use App\Models\Personnel;
use Illuminate\Support\Collection;

class MyHrOnboardingReadService
{
    public function build(Personnel $personnel): array
    {
        $assignments = OnboardingDocumentAssignment::query()
            ->where('personnel_id', $personnel->id)
            ->with(['template', 'receipt'])
            ->latest('assigned_at')
            ->get();

        return [
            'summary' => $this->summary($assignments),
            'rows' => $assignments->map(fn (OnboardingDocumentAssignment $assignment) => $this->mapRow($assignment))->all(),
        ];
    }

    protected function summary(Collection $assignments): array
    {
        return [
            'total' => $assignments->count(),
            'pending' => $assignments->filter(fn (OnboardingDocumentAssignment $assignment) => in_array($this->resolveStatus($assignment), ['assigned', 'opened', 'overdue'], true))->count(),
            'acknowledged' => $assignments->filter(fn (OnboardingDocumentAssignment $assignment) => $this->resolveStatus($assignment) === 'acknowledged')->count(),
            'required' => $assignments->filter(fn (OnboardingDocumentAssignment $assignment) => (bool) $assignment->template?->is_required)->count(),
        ];
    }

    protected function mapRow(OnboardingDocumentAssignment $assignment): array
    {
        $template = $assignment->template;
        $receipt = $assignment->receipt;
        $status = $this->resolveStatus($assignment);

        return [
            'id' => $assignment->id,
            'title' => $template?->title ?: __('personnel::my_hr.onboarding.messages.missing_template'),
            'document_type' => $template?->document_type ?: 'other',
            'document_type_label' => __('personnel::my_hr.onboarding.document_types.'.($template?->document_type ?: 'other')),
            'version' => $template?->version ?: '—',
            'status' => $status,
            'status_label' => __('personnel::my_hr.onboarding.status.'.$status),
            'status_mode' => match ($status) {
                'acknowledged' => 'emerald',
                'overdue' => 'rose',
                'opened' => 'sky',
                default => 'muted',
            },
            'is_required' => (bool) $template?->is_required,
            'requires_acknowledgement' => (bool) $template?->requires_acknowledgement,
            'assigned_at' => optional($assignment->assigned_at)->format('d.m.Y H:i') ?: '—',
            'due_at' => optional($assignment->due_at)->format('d.m.Y H:i'),
            'opened_at' => optional($receipt?->opened_at)->format('d.m.Y H:i'),
            'acknowledged_at' => optional($receipt?->acknowledged_at)->format('d.m.Y H:i'),
            'file_url' => $template?->fileUrl(),
            'can_acknowledge' => (bool) $template?->requires_acknowledgement && blank($receipt?->acknowledged_at),
        ];
    }

    protected function resolveStatus(OnboardingDocumentAssignment $assignment): string
    {
        $receipt = $assignment->receipt;

        if ($assignment->status === 'waived') {
            return 'waived';
        }

        if (filled($receipt?->acknowledged_at)) {
            return 'acknowledged';
        }

        if (filled($assignment->due_at) && $assignment->due_at->isPast()) {
            return 'overdue';
        }

        if (filled($receipt?->opened_at)) {
            return 'opened';
        }

        return 'assigned';
    }
}
