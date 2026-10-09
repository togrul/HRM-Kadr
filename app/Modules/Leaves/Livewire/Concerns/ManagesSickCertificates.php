<?php

namespace App\Modules\Leaves\Livewire\Concerns;

use App\Models\LeaveSickCertificate;
use App\Modules\Leaves\Application\Services\SickCertificateService;
use Illuminate\Validation\ValidationException;

/**
 * Row actions shared by the certificate register and the employee card's sick tab. Edit,
 * close and extend open the editor panel (SickCertificateEditor) from the browser; cancel
 * runs here, after the shared confirmation modal has collected a reason.
 */
trait ManagesSickCertificates
{
    public function cancelCertificate(int $certificateId, string $reason = ''): void
    {
        $certificate = LeaveSickCertificate::query()->findOrFail($certificateId);
        $this->authorize('update', $certificate);

        try {
            app(SickCertificateService::class)->cancel($certificate, $reason, auth()->user());
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('leaves::sick_certificates.messages.cancelled'));
        $this->dispatch('sick-certificates-changed');
    }
}
