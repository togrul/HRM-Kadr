<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Models\TrainingDeliveryRecord;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class CertificateViewer extends Component
{
    use InteractsWithTrainingNeedsAccess;

    public ?int $deliveryRecordId = null;
    public array $recordSnapshot = [];
    public ?string $temporaryCertificateName = null;
    public ?string $temporaryCertificatePreviewUrl = null;
    public ?string $temporaryCertificateExtension = null;
    public bool $hasPendingUpload = false;

    public function mount(
        ?int $deliveryRecordId = null,
        array $recordSnapshot = [],
        ?string $temporaryCertificateName = null,
        ?string $temporaryCertificatePreviewUrl = null,
        ?string $temporaryCertificateExtension = null,
        bool $hasPendingUpload = false,
    ): void
    {
        $this->authorizeTrainingNeedsView();
        $this->deliveryRecordId = $deliveryRecordId;
        $this->recordSnapshot = $recordSnapshot;
        $this->temporaryCertificateName = $temporaryCertificateName;
        $this->temporaryCertificatePreviewUrl = $temporaryCertificatePreviewUrl;
        $this->temporaryCertificateExtension = $temporaryCertificateExtension;
        $this->hasPendingUpload = $hasPendingUpload;
    }

    public function getRecordProperty(): ?TrainingDeliveryRecord
    {
        if ($this->recordSnapshot !== []) {
            $record = new TrainingDeliveryRecord();
            $record->forceFill([
                'id' => data_get($this->recordSnapshot, 'id'),
                'certificate_path' => data_get($this->recordSnapshot, 'certificate_path'),
                'certificate_name' => data_get($this->recordSnapshot, 'certificate_name'),
                'result_status' => data_get($this->recordSnapshot, 'result_status'),
                'completed_at' => data_get($this->recordSnapshot, 'completed_at'),
            ]);
            $record->syncOriginal();

            $record->setRelation('session', (object) [
                'title' => data_get($this->recordSnapshot, 'session.title'),
            ]);
            $record->setRelation('program', (object) [
                'title' => data_get($this->recordSnapshot, 'program.title'),
            ]);
            $record->setRelation('personnel', (object) [
                'fullname' => data_get($this->recordSnapshot, 'personnel.fullname'),
            ]);

            return $record;
        }

        if (! $this->deliveryRecordId) {
            return null;
        }

        return TrainingDeliveryRecord::query()
            ->with([
                'session:id,title',
                'personnel:id,surname,name,patronymic,tabel_no',
                'program:id,title',
            ])
            ->find($this->deliveryRecordId);
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.certificate-viewer');
    }
}
