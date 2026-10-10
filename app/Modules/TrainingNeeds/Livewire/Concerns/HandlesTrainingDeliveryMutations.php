<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingDeliveryRecord;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use App\Modules\TrainingNeeds\Exports\TrainingNeedsReportExport;
use App\Modules\TrainingNeeds\Support\TrainingStructureScope;
use App\Support\Uploads\PrivateFiles;
use App\Support\Uploads\SecureFileResponse;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesTrainingDeliveryMutations
{
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

        $record = TrainingStructureScope::onPersonnelColumn(TrainingDeliveryRecord::query(), 'training_delivery_records.personnel_id')->findOrFail((int) data_get($validated, 'deliveryDocumentForm.training_delivery_record_id'));
        $file = data_get($this->deliveryDocumentForm, 'certificate_file');

        if ($file instanceof TemporaryUploadedFile) {
            $storedPath = $file->store('training-certificates', PrivateFiles::DISK);

            if ($record->certificate_path) {
                PrivateFiles::delete($record->certificate_path);
            }

            $record->update([
                'certificate_path' => $storedPath,
                'certificate_name' => $file->getClientOriginalName(),
            ]);
        }

        $this->reset('deliveryDocumentForm', 'searchDeliveryRecord');
        $this->deliveryDocumentForm = $this->deliveryDocumentDefaults();
        $this->refreshRuntimeCaches();
        $this->refreshResultsSummary();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.certificate_saved'));
    }

    public function previewDeliveryCertificate(int $deliveryRecordId): StreamedResponse
    {
        $this->authorizeTrainingNeedsView();
        $record = TrainingStructureScope::onPersonnelColumn(TrainingDeliveryRecord::query(), 'training_delivery_records.personnel_id')->findOrFail($deliveryRecordId);
        $path = (string) $record->getAttribute('certificate_path');
        $disk = PrivateFiles::locate($path);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk($disk, $path, $record->getAttribute('certificate_name'), inline: true);
    }

    public function downloadDeliveryCertificate(int $deliveryRecordId): StreamedResponse
    {
        $this->authorizeTrainingNeedsView();
        $record = TrainingStructureScope::onPersonnelColumn(TrainingDeliveryRecord::query(), 'training_delivery_records.personnel_id')->findOrFail($deliveryRecordId);
        $path = (string) $record->getAttribute('certificate_path');
        $disk = PrivateFiles::locate($path);
        abort_if($disk === null, 404);

        return SecureFileResponse::fromDisk($disk, $path, $record->getAttribute('certificate_name') ?: basename($path));
    }

    public function deleteDeliveryCertificate(int $deliveryRecordId): void
    {
        $this->authorizeTrainingNeedsManage();
        $record = TrainingStructureScope::onPersonnelColumn(TrainingDeliveryRecord::query(), 'training_delivery_records.personnel_id')->findOrFail($deliveryRecordId);

        if ($record->certificate_path) {
            PrivateFiles::delete($record->certificate_path);
        }

        $record->update([
            'certificate_path' => null,
            'certificate_name' => null,
        ]);

        if ((int) data_get($this->deliveryDocumentForm, 'training_delivery_record_id') === $deliveryRecordId) {
            $this->deliveryDocumentForm['certificate_file'] = null;
        }

        $this->refreshRuntimeCaches();
        $this->refreshResultsSummary();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.certificate_deleted'));
    }

    public function exportDeliveryReport(): BinaryFileResponse
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

    public function exportDeliverySummaryReport(): BinaryFileResponse
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

    public function exportDeliveryPivotReport(): BinaryFileResponse
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

    public function exportFeedbackReport(): BinaryFileResponse
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

    public function exportAuditReport(): BinaryFileResponse
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
