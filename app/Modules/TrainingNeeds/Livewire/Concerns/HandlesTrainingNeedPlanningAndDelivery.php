<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingFeedbackResponse;
use App\Models\TrainingPlanItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use App\Modules\TrainingNeeds\Application\Services\TrainingDeliveryService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedPlanningService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionParticipantService;
use App\Modules\TrainingNeeds\Exports\TrainingNeedsReportExport;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesTrainingNeedPlanningAndDelivery
{
    public function selectVisibleSessionProposals(): void
    {
        $this->bulkProposalPlanItemIds = collect($this->sessionProposals)
            ->pluck('plan_item_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function clearSelectedSessionProposals(): void
    {
        $this->bulkProposalPlanItemIds = [];
    }

    public function createSelectedSessionProposals(): void
    {
        $this->authorizeTrainingNeedsManage();

        $validated = $this->validate([
            'bulkProposalPlanItemIds' => 'required|array|min:1',
            'bulkProposalPlanItemIds.*' => 'integer|exists:training_plan_items,id',
        ]);

        $created = 0;

        foreach ($validated['bulkProposalPlanItemIds'] as $planItemId) {
            $proposal = app(\App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService::class)
                ->proposals()
                ->firstWhere('plan_item_id', (int) $planItemId);

            if ($proposal === null) {
                continue;
            }

            $session = TrainingSession::query()->create([
                'training_plan_item_id' => $proposal['plan_item_id'],
                'training_annual_plan_id' => $proposal['training_annual_plan_id'],
                'training_program_id' => $proposal['training_program_id'],
                'title' => $proposal['title'],
                'scheduled_start_at' => $proposal['scheduled_start_at'],
                'scheduled_end_at' => $proposal['scheduled_end_at'],
                'location' => $proposal['location'],
                'trainer_name' => $proposal['trainer_name'],
                'capacity' => $proposal['capacity'],
                'planned_budget' => $proposal['planned_budget'],
                'auto_fill_participants' => (bool) $proposal['auto_fill_participants'],
                'status' => $proposal['status'],
                'notes' => $proposal['notes'],
            ]);

            if ($session->auto_fill_participants) {
                app(TrainingSessionParticipantService::class)->autoFillFromNeeds($session->load('plan'));
            }

            $created++;
        }

        $this->bulkProposalPlanItemIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_session_proposals_created', ['count' => $created]));
    }

    public function storePlan(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'planForm.title' => 'required|string|min:2|max:160',
            'planForm.plan_year' => 'required|integer|min:2020|max:2100',
            'planForm.plan_quarter' => 'nullable|integer|min:1|max:4',
            'planForm.status' => 'required|in:draft,review,approved,published',
            'planForm.notes' => 'nullable|string|max:2000',
            'planForm.auto_generate' => 'nullable|boolean',
        ], attributes: [
            'planForm.title' => __('training_needs::dashboard.fields.plan_title'),
            'planForm.plan_year' => __('training_needs::dashboard.fields.plan_year'),
            'planForm.plan_quarter' => __('training_needs::dashboard.fields.plan_quarter'),
            'planForm.status' => __('training_needs::dashboard.fields.status'),
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => trim((string) data_get($validated, 'planForm.title')),
            'plan_year' => (int) data_get($validated, 'planForm.plan_year'),
            'plan_quarter' => data_get($validated, 'planForm.plan_quarter'),
            'status' => (string) data_get($validated, 'planForm.status'),
            'notes' => data_get($validated, 'planForm.notes'),
            'auto_generated' => (bool) (data_get($validated, 'planForm.auto_generate') ?? true),
        ]);

        if ((bool) (data_get($validated, 'planForm.auto_generate') ?? true)) {
            app(TrainingNeedPlanningService::class)->generatePlanItems($plan);
        } else {
            app(TrainingNeedPlanningService::class)->syncPlanStatus($plan);
        }

        $this->reset('planForm');
        $this->planForm = $this->planDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.plan_saved'));
    }

    public function savePlanItemReview(string $status): void
    {
        $this->authorizeTrainingNeedsReview();
        abort_unless(in_array($status, ['hr_adjusted', 'approved'], true), 404);

        $validated = $this->validate([
            'selectedPlanItemId' => 'required|exists:training_plan_items,id',
            'planItemReviewForm.participant_count' => 'required|integer|min:1|max:10000',
            'planItemReviewForm.estimated_budget' => 'nullable|numeric|min:0|max:99999999.99',
            'planItemReviewForm.priority' => 'required|in:low,medium,high',
            'planItemReviewForm.review_note' => 'nullable|string|max:2000',
        ], attributes: [
            'selectedPlanItemId' => __('training_needs::dashboard.fields.plan_item'),
            'planItemReviewForm.participant_count' => __('training_needs::dashboard.fields.participant_count'),
            'planItemReviewForm.estimated_budget' => __('training_needs::dashboard.fields.planned_budget'),
            'planItemReviewForm.priority' => __('training_needs::dashboard.fields.priority'),
            'planItemReviewForm.review_note' => __('training_needs::dashboard.fields.review_note'),
        ]);

        $item = TrainingPlanItem::query()->findOrFail((int) $validated['selectedPlanItemId']);
        $item->update([
            'participant_count' => (int) data_get($validated, 'planItemReviewForm.participant_count'),
            'estimated_budget' => data_get($validated, 'planItemReviewForm.estimated_budget'),
            'priority' => (string) data_get($validated, 'planItemReviewForm.priority'),
            'review_note' => data_get($validated, 'planItemReviewForm.review_note'),
            'review_status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        app(TrainingNeedPlanningService::class)->syncPlanStatus($item->plan()->firstOrFail());

        $this->selectedPlanItemId = $item->id;
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.plan_item_review_saved', [
            'status' => __('training_needs::dashboard.review_statuses.'.$status),
        ]));
    }

    public function storeSession(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'sessionForm.training_annual_plan_id' => 'nullable|exists:training_annual_plans,id',
            'sessionForm.training_program_id' => 'nullable|exists:training_programs,id',
            'sessionForm.title' => 'nullable|string|min:2|max:160',
            'sessionForm.scheduled_start_at' => 'required|date',
            'sessionForm.scheduled_end_at' => 'nullable|date|after_or_equal:sessionForm.scheduled_start_at',
            'sessionForm.location' => 'nullable|string|max:160',
            'sessionForm.trainer_name' => 'nullable|string|max:160',
            'sessionForm.capacity' => 'nullable|integer|min:1|max:5000',
            'sessionForm.planned_budget' => 'nullable|numeric|min:0|max:9999999.99',
            'sessionForm.auto_fill_participants' => 'nullable|boolean',
            'sessionForm.status' => 'required|in:draft,scheduled,in_progress,completed,cancelled',
            'sessionForm.notes' => 'nullable|string|max:2000',
        ], attributes: [
            'sessionForm.training_annual_plan_id' => __('training_needs::dashboard.fields.plan'),
            'sessionForm.training_program_id' => __('training_needs::dashboard.fields.program'),
            'sessionForm.title' => __('training_needs::dashboard.fields.session_title'),
            'sessionForm.scheduled_start_at' => __('training_needs::dashboard.fields.scheduled_start_at'),
            'sessionForm.scheduled_end_at' => __('training_needs::dashboard.fields.scheduled_end_at'),
            'sessionForm.location' => __('training_needs::dashboard.fields.location'),
            'sessionForm.trainer_name' => __('training_needs::dashboard.fields.trainer_name'),
            'sessionForm.capacity' => __('training_needs::dashboard.fields.capacity'),
            'sessionForm.planned_budget' => __('training_needs::dashboard.fields.planned_budget'),
            'sessionForm.auto_fill_participants' => __('training_needs::dashboard.fields.auto_fill_participants'),
            'sessionForm.status' => __('training_needs::dashboard.fields.status'),
        ]);

        $program = null;
        if (data_get($validated, 'sessionForm.training_program_id')) {
            $program = TrainingProgram::query()->find(data_get($validated, 'sessionForm.training_program_id'));
        }

        $session = TrainingSession::query()->create([
            'training_plan_item_id' => $this->selectedSessionProposalPlanItemId,
            'training_annual_plan_id' => data_get($validated, 'sessionForm.training_annual_plan_id'),
            'training_program_id' => data_get($validated, 'sessionForm.training_program_id'),
            'title' => trim((string) (data_get($validated, 'sessionForm.title') ?: ($program?->title ?: __('training_needs::dashboard.labels.default_session_title')))),
            'scheduled_start_at' => data_get($validated, 'sessionForm.scheduled_start_at'),
            'scheduled_end_at' => data_get($validated, 'sessionForm.scheduled_end_at'),
            'location' => blank(data_get($validated, 'sessionForm.location')) ? null : trim((string) data_get($validated, 'sessionForm.location')),
            'trainer_name' => blank(data_get($validated, 'sessionForm.trainer_name')) ? null : trim((string) data_get($validated, 'sessionForm.trainer_name')),
            'capacity' => data_get($validated, 'sessionForm.capacity'),
            'planned_budget' => data_get($validated, 'sessionForm.planned_budget'),
            'auto_fill_participants' => (bool) (data_get($validated, 'sessionForm.auto_fill_participants') ?? true),
            'status' => (string) data_get($validated, 'sessionForm.status'),
            'notes' => data_get($validated, 'sessionForm.notes'),
        ]);

        $filledParticipants = 0;
        if ($session->auto_fill_participants) {
            $filledParticipants = app(TrainingSessionParticipantService::class)->autoFillFromNeeds($session->load('plan'));
        }

        if ($session->training_annual_plan_id) {
            app(TrainingNeedPlanningService::class)->syncPlanStatus($session->plan()->first());
        }

        $this->reset('sessionForm', 'searchSessionPlan', 'searchSessionProgram');
        $this->sessionForm = $this->sessionDefaults();
        $this->selectedSessionProposalPlanItemId = null;
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_saved', [
            'count' => $filledParticipants,
        ]));
    }

    public function storeSessionParticipant(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'participantForm.training_session_id' => 'required|exists:training_sessions,id',
            'participantForm.personnel_id' => 'required|exists:personnels,id',
            'participantForm.training_need_item_id' => 'nullable|exists:training_need_items,id',
            'participantForm.attendance_status' => 'required|in:planned,confirmed,attended,absent,cancelled',
        ], attributes: [
            'participantForm.training_session_id' => __('training_needs::dashboard.fields.session'),
            'participantForm.personnel_id' => __('training_needs::dashboard.fields.personnel'),
            'participantForm.training_need_item_id' => __('training_needs::dashboard.fields.training_need'),
            'participantForm.attendance_status' => __('training_needs::dashboard.fields.attendance_status'),
        ]);

        TrainingSessionParticipant::query()->updateOrCreate(
            [
                'training_session_id' => (int) data_get($validated, 'participantForm.training_session_id'),
                'personnel_id' => (int) data_get($validated, 'participantForm.personnel_id'),
            ],
            [
                'training_need_item_id' => data_get($validated, 'participantForm.training_need_item_id'),
                'attendance_status' => (string) data_get($validated, 'participantForm.attendance_status'),
                'attended_at' => in_array((string) data_get($validated, 'participantForm.attendance_status'), ['attended', 'completed'], true) ? now() : null,
            ]
        );

        $this->reset('participantForm', 'searchSession', 'searchPersonnel');
        $this->participantForm = $this->participantDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.participant_saved'));
    }

    public function quickSetParticipantStatus(int $participantId, string $status): void
    {
        $this->authorizeTrainingNeedsManage();
        abort_unless(in_array($status, ['planned', 'confirmed', 'attended', 'absent', 'cancelled'], true), 404);

        $participant = TrainingSessionParticipant::query()->findOrFail($participantId);
        $participant->forceFill([
            'attendance_status' => $status,
            'attended_at' => $status === 'attended' ? ($participant->attended_at ?? now()) : null,
        ])->save();

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.participant_status_updated'));
    }

    public function selectVisibleParticipants(): void
    {
        $this->bulkParticipantIds = collect($this->filteredSelectedParticipants)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function clearSelectedParticipants(): void
    {
        $this->bulkParticipantIds = [];
    }

    public function applyBulkParticipantStatusShortcut(string $status): void
    {
        $this->bulkAttendanceStatus = $status;
        $this->applyBulkParticipantStatus();
    }

    public function applyBulkParticipantStatus(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'selectedSessionId' => 'required|exists:training_sessions,id',
            'bulkAttendanceStatus' => 'required|in:planned,confirmed,attended,absent,cancelled',
            'bulkParticipantIds' => 'required|array|min:1',
            'bulkParticipantIds.*' => 'integer|exists:training_session_participants,id',
        ], attributes: [
            'selectedSessionId' => __('training_needs::dashboard.fields.session'),
            'bulkAttendanceStatus' => __('training_needs::dashboard.fields.attendance_status'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        $status = (string) $validated['bulkAttendanceStatus'];

        TrainingSessionParticipant::query()
            ->where('training_session_id', (int) $validated['selectedSessionId'])
            ->whereIn('id', $validated['bulkParticipantIds'])
            ->get()
            ->each(function (TrainingSessionParticipant $participant) use ($status): void {
                $participant->forceFill([
                    'attendance_status' => $status,
                    'attended_at' => $status === 'attended' ? ($participant->attended_at ?? now()) : null,
                ])->save();
            });

        $count = count($validated['bulkParticipantIds']);
        $this->bulkParticipantIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_participants_updated', [
            'count' => $count,
        ]));
    }

    public function removeSelectedParticipants(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'selectedSessionId' => 'required|exists:training_sessions,id',
            'bulkParticipantIds' => 'required|array|min:1',
            'bulkParticipantIds.*' => 'integer|exists:training_session_participants,id',
        ], attributes: [
            'selectedSessionId' => __('training_needs::dashboard.fields.session'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        TrainingSessionParticipant::query()
            ->where('training_session_id', (int) $validated['selectedSessionId'])
            ->whereIn('id', $validated['bulkParticipantIds'])
            ->delete();

        $count = count($validated['bulkParticipantIds']);
        $this->bulkParticipantIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_participants_removed', [
            'count' => $count,
        ]));
    }

    public function completeSession(TrainingDeliveryService $service): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'participantForm.training_session_id' => 'required|exists:training_sessions,id',
        ], attributes: [
            'participantForm.training_session_id' => __('training_needs::dashboard.fields.session'),
        ]);

        $session = TrainingSession::query()->findOrFail((int) data_get($validated, 'participantForm.training_session_id'));
        $stats = $service->completeSession($session);

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_completed', [
            'attended' => $stats['attended_count'],
            'records' => $stats['record_count'],
        ]));
    }

    public function storeFeedbackForm(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'feedbackForm.training_session_id' => 'required|exists:training_sessions,id',
            'feedbackForm.title' => 'required|string|min:2|max:160',
            'feedbackForm.status' => 'required|in:draft,open,closed',
            'feedbackForm.default_question_type' => 'required|in:rating,text,multiple_choice',
            'feedbackForm.questions_text' => 'nullable|string|max:2000',
        ], attributes: [
            'feedbackForm.training_session_id' => __('training_needs::dashboard.fields.session'),
            'feedbackForm.title' => __('training_needs::dashboard.fields.feedback_title'),
            'feedbackForm.status' => __('training_needs::dashboard.fields.status'),
            'feedbackForm.default_question_type' => __('training_needs::dashboard.fields.default_question_type'),
            'feedbackForm.questions_text' => __('training_needs::dashboard.fields.feedback_questions'),
        ]);

        $questions = collect(preg_split('/\r\n|\r|\n/', (string) data_get($validated, 'feedbackForm.questions_text')))
            ->map(fn ($question) => trim((string) $question))
            ->filter()
            ->values()
            ->map(fn ($question) => [
                'type' => (string) data_get($validated, 'feedbackForm.default_question_type'),
                'text' => $question,
            ])
            ->all();

        TrainingFeedbackForm::query()->create([
            'training_session_id' => (int) data_get($validated, 'feedbackForm.training_session_id'),
            'title' => trim((string) data_get($validated, 'feedbackForm.title')),
            'status' => (string) data_get($validated, 'feedbackForm.status'),
            'questions' => $questions,
        ]);

        $this->reset('feedbackForm', 'searchSession');
        $this->feedbackForm = $this->feedbackFormDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.feedback_form_saved'));
    }

    public function submitFeedbackResponse(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'feedbackResponseForm.training_feedback_form_id' => 'required|exists:training_feedback_forms,id',
            'feedbackResponseForm.personnel_id' => 'required|exists:personnels,id',
            'feedbackResponseForm.overall_score' => 'required|integer|min:1|max:5',
            'feedbackResponseForm.comments' => 'nullable|string|max:2000',
            'feedbackResponseForm.answers_text' => 'nullable|string|max:3000',
        ], attributes: [
            'feedbackResponseForm.training_feedback_form_id' => __('training_needs::dashboard.fields.feedback_form'),
            'feedbackResponseForm.personnel_id' => __('training_needs::dashboard.fields.personnel'),
            'feedbackResponseForm.overall_score' => __('training_needs::dashboard.fields.overall_score'),
            'feedbackResponseForm.comments' => __('training_needs::dashboard.fields.comments'),
            'feedbackResponseForm.answers_text' => __('training_needs::dashboard.fields.feedback_answers'),
        ]);

        $form = TrainingFeedbackForm::query()->findOrFail((int) data_get($validated, 'feedbackResponseForm.training_feedback_form_id'));

        $answers = collect(preg_split('/\r\n|\r|\n/', (string) data_get($validated, 'feedbackResponseForm.answers_text')))
            ->map(fn ($answer) => trim((string) $answer))
            ->filter()
            ->values()
            ->all();

        TrainingFeedbackResponse::query()->updateOrCreate(
            [
                'training_feedback_form_id' => $form->id,
                'personnel_id' => (int) data_get($validated, 'feedbackResponseForm.personnel_id'),
            ],
            [
                'training_session_id' => $form->training_session_id,
                'overall_score' => (int) data_get($validated, 'feedbackResponseForm.overall_score'),
                'comments' => data_get($validated, 'feedbackResponseForm.comments'),
                'answers' => $answers,
                'submitted_at' => now(),
            ]
        );

        $this->reset('feedbackResponseForm', 'searchFeedbackForm', 'searchPersonnel');
        $this->feedbackResponseForm = $this->feedbackResponseDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.feedback_response_saved'));
    }

    public function storeDeliveryDocument(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'deliveryDocumentForm.training_delivery_record_id' => 'required|exists:training_delivery_records,id',
            'deliveryDocumentForm.certificate_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
        ], attributes: [
            'deliveryDocumentForm.training_delivery_record_id' => __('training_needs::dashboard.fields.delivery_record'),
            'deliveryDocumentForm.certificate_file' => __('training_needs::dashboard.fields.certificate_file'),
        ]);

        $record = TrainingDeliveryRecord::query()->findOrFail((int) data_get($validated, 'deliveryDocumentForm.training_delivery_record_id'));
        $file = data_get($this->deliveryDocumentForm, 'certificate_file');

        if ($file instanceof TemporaryUploadedFile) {
            $storedPath = $file->store('training-certificates', 'public');

            if ($record->certificate_path) {
                Storage::disk('public')->delete($record->certificate_path);
            }

            $record->update([
                'certificate_path' => $storedPath,
                'certificate_name' => $file->getClientOriginalName(),
            ]);
        }

        $this->reset('deliveryDocumentForm', 'searchDeliveryRecord');
        $this->deliveryDocumentForm = $this->deliveryDocumentDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.certificate_saved'));
    }

    public function previewDeliveryCertificate(int $deliveryRecordId)
    {
        $this->authorizeTrainingNeedsView();
        $record = TrainingDeliveryRecord::query()->findOrFail($deliveryRecordId);
        abort_unless($record->certificate_path, 404);

        return response()->file(
            Storage::disk('public')->path($record->certificate_path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($record->certificate_path) ?: 'application/octet-stream',
            ]
        );
    }

    public function downloadDeliveryCertificate(int $deliveryRecordId): StreamedResponse
    {
        $this->authorizeTrainingNeedsView();
        $record = TrainingDeliveryRecord::query()->findOrFail($deliveryRecordId);
        abort_unless($record->certificate_path, 404);

        return Storage::disk('public')->download(
            $record->certificate_path,
            $record->certificate_name ?: basename($record->certificate_path)
        );
    }

    public function deleteDeliveryCertificate(int $deliveryRecordId): void
    {
        $this->authorizeTrainingNeedsManage();
        $record = TrainingDeliveryRecord::query()->findOrFail($deliveryRecordId);

        if ($record->certificate_path) {
            Storage::disk('public')->delete($record->certificate_path);
        }

        $record->update([
            'certificate_path' => null,
            'certificate_name' => null,
        ]);

        if ((int) data_get($this->deliveryDocumentForm, 'training_delivery_record_id') === $deliveryRecordId) {
            $this->deliveryDocumentForm['certificate_file'] = null;
        }

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.certificate_deleted'));
    }

    public function exportDeliveryReport()
    {
        $this->authorizeTrainingNeedsExport();
        $rows = app(TrainingNeedReportingService::class)->deliveryRows();

        return Excel::download(
            new TrainingNeedsReportExport(
                $rows,
                [
                    __('training_needs::dashboard.fields.session_title'),
                    __('training_needs::dashboard.fields.program'),
                    __('training_needs::dashboard.fields.competency'),
                    __('training_needs::dashboard.fields.personnel'),
                    __('training_needs::dashboard.fields.tabel_no'),
                    __('training_needs::dashboard.fields.scheduled_start_at'),
                    __('training_needs::dashboard.fields.location'),
                    __('training_needs::dashboard.fields.duration_hours'),
                    __('training_needs::dashboard.fields.completed_at'),
                    __('training_needs::dashboard.fields.certificate_name'),
                ],
                'delivery'
            ),
            'training-delivery-report.xlsx'
        );
    }

    public function exportFeedbackReport()
    {
        $this->authorizeTrainingNeedsExport();
        $rows = app(TrainingNeedReportingService::class)->feedbackRows();

        return Excel::download(
            new TrainingNeedsReportExport(
                $rows,
                [
                    __('training_needs::dashboard.fields.session_title'),
                    __('training_needs::dashboard.fields.feedback_form'),
                    __('training_needs::dashboard.fields.personnel'),
                    __('training_needs::dashboard.fields.tabel_no'),
                    __('training_needs::dashboard.fields.overall_score'),
                    __('training_needs::dashboard.fields.submitted_at'),
                    __('training_needs::dashboard.fields.comments'),
                ],
                'feedback'
            ),
            'training-feedback-report.xlsx'
        );
    }

    public function exportDeliverySummaryReport()
    {
        $this->authorizeTrainingNeedsExport();
        $rows = app(TrainingNeedReportingService::class)->deliverySummaryRows();

        return Excel::download(
            new TrainingNeedsReportExport(
                $rows,
                [
                    __('training_needs::dashboard.fields.session_title'),
                    __('training_needs::dashboard.fields.program'),
                    __('training_needs::dashboard.fields.scheduled_start_at'),
                    __('training_needs::dashboard.fields.status'),
                    __('training_needs::dashboard.fields.participant_count'),
                    __('training_needs::dashboard.fields.attendance_status'),
                    __('training_needs::dashboard.fields.delivery_records_count'),
                    __('training_needs::dashboard.fields.average_feedback_score'),
                ],
                'delivery_summary'
            ),
            'training-delivery-summary-report.xlsx'
        );
    }

    public function exportDeliveryPivotReport()
    {
        $this->authorizeTrainingNeedsExport();
        $rows = app(TrainingNeedReportingService::class)->deliveryPivotRows();

        return Excel::download(
            new TrainingNeedsReportExport(
                $rows,
                [
                    __('training_needs::dashboard.fields.program'),
                    __('training_needs::dashboard.fields.delivery_type'),
                    __('training_needs::dashboard.fields.sessions_count'),
                    __('training_needs::dashboard.fields.attendance_status'),
                    __('training_needs::dashboard.fields.delivery_records_count'),
                    __('training_needs::dashboard.fields.certificates_uploaded'),
                    __('training_needs::dashboard.fields.average_feedback_score'),
                ],
                'delivery_pivot'
            ),
            'training-delivery-pivot-report.xlsx'
        );
    }

    public function exportAuditReport()
    {
        $this->authorizeTrainingNeedsExport();
        $rows = app(TrainingNeedReportingService::class)->auditRows();

        return Excel::download(
            new TrainingNeedsReportExport(
                $rows,
                [
                    __('training_needs::dashboard.fields.audit_subject'),
                    __('training_needs::dashboard.fields.audit_subject_id'),
                    __('training_needs::dashboard.fields.audit_event'),
                    __('training_needs::dashboard.fields.audit_actor'),
                    __('training_needs::dashboard.fields.audit_created_at'),
                    __('training_needs::dashboard.fields.audit_properties'),
                ],
                'audit'
            ),
            'training-audit-report.xlsx'
        );
    }
}
