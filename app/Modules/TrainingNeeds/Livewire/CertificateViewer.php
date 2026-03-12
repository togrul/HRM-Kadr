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

    public function mount(?int $deliveryRecordId = null): void
    {
        $this->authorizeTrainingNeedsView();
        $this->deliveryRecordId = $deliveryRecordId;
    }

    public function getRecordProperty(): ?TrainingDeliveryRecord
    {
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
