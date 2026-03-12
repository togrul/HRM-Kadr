<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingFeedbackResponse;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use App\Modules\TrainingNeeds\Exports\TrainingNeedsReportExport;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesTrainingResultsMutations
{
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
                    __('training_needs::dashboard.labels.attended_participants'),
                    __('training_needs::dashboard.labels.delivery_records'),
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
                    __('training_needs::dashboard.labels.attended_participants'),
                    __('training_needs::dashboard.fields.delivery_records_count'),
                    __('training_needs::dashboard.fields.certificates_uploaded'),
                    __('training_needs::dashboard.fields.average_feedback_score'),
                ],
                'delivery_pivot'
            ),
            'training-delivery-pivot-report.xlsx'
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
