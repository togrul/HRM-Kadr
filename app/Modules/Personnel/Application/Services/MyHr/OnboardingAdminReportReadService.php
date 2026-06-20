<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\OnboardingDocumentAssignment;
use App\Models\OnboardingDocumentTemplate;
use App\Models\Personnel;

class OnboardingAdminReportReadService
{
    public function build(Personnel $personnel): array
    {
        $templateStats = OnboardingDocumentTemplate::query()
            ->withCount([
                'assignments',
                'assignments as acknowledged_assignments_count' => fn ($query) => $query->whereHas('receipt', fn ($receipt) => $receipt->whereNotNull('acknowledged_at')),
                'assignments as overdue_assignments_count' => fn ($query) => $query
                    ->where(function ($inner): void {
                        $inner->where('status', 'overdue')
                            ->orWhere(function ($nested): void {
                                $nested->whereNotNull('due_at')
                                    ->where('due_at', '<', now())
                                    ->whereDoesntHave('receipt', fn ($receipt) => $receipt->whereNotNull('acknowledged_at'));
                            });
                    }),
            ])
            ->latest('created_at')
            ->limit(8)
            ->get([
                'id',
                'title',
                'document_type',
                'version',
                'is_required',
                'requires_acknowledgement',
            ]);

        return [
            'summary' => [
                'template_total' => OnboardingDocumentTemplate::query()->count(),
                'required_templates' => OnboardingDocumentTemplate::query()->where('is_required', true)->count(),
                'employee_total' => OnboardingDocumentAssignment::query()->where('personnel_id', $personnel->id)->count(),
                'employee_overdue' => OnboardingDocumentAssignment::query()
                    ->where('personnel_id', $personnel->id)
                    ->where(function ($query): void {
                        $query->where('status', 'overdue')
                            ->orWhere(function ($nested): void {
                                $nested->whereNotNull('due_at')
                                    ->where('due_at', '<', now())
                                    ->whereDoesntHave('receipt', fn ($receipt) => $receipt->whereNotNull('acknowledged_at'));
                            });
                    })
                    ->count(),
            ],
            'templates' => $templateStats->map(fn (OnboardingDocumentTemplate $template): array => [
                'id' => $template->id,
                'title' => $template->title,
                'document_type_label' => __('personnel::my_hr.onboarding.document_types.'.$template->document_type),
                'version' => $template->version,
                'assignments_count' => $template->assignments_count,
                'acknowledged_assignments_count' => $template->acknowledged_assignments_count,
                'overdue_assignments_count' => $template->overdue_assignments_count,
                'required' => (bool) $template->is_required,
                'requires_acknowledgement' => (bool) $template->requires_acknowledgement,
                'file_url' => $template->fileUrl(),
            ])->all(),
        ];
    }
}
