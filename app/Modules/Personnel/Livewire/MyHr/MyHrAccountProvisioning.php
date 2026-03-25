<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MyHrAccountProvisioning extends Component
{
    use AuthorizesRequests;

    public int $personnelModel;

    public Personnel $personnel;

    public ?string $resetUrl = null;

    public string $searchLinkedUser = '';

    public array $manualLink = [
        'user_id' => null,
    ];

    public function mount(int $personnelModel): void
    {
        $this->authorize('manage-my-hr-accounts');
        $this->personnel = Personnel::query()->with(['position', 'structure'])->findOrFail($personnelModel);
        $this->syncManualLinkSelection();
    }

    public function provision(): void
    {
        $this->authorize('manage-my-hr-accounts');
        $this->resetValidation();

        try {
            $result = app(MyHrAccountProvisioningService::class)->provision($this->personnel->fresh());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->resetUrl = $result['reset_url'];
        $this->personnel = $this->personnel->fresh(['position', 'structure']);
        $this->syncManualLinkSelection();

        $this->dispatch(
            'notify',
            type: 'success',
            message: $result['created']
                ? __('personnel::my_hr_account.messages.account_created')
                : __('personnel::my_hr_account.messages.reset_link_regenerated')
        );
    }

    public function saveManualLink(): void
    {
        $this->authorize('manage-my-hr-accounts');

        $validated = $this->validate([
            'manualLink.user_id' => 'required|exists:users,id',
        ], attributes: [
            'manualLink.user_id' => __('personnel::my_hr_account.labels.user_email'),
        ]);

        try {
            app(MyHrAccountProvisioningService::class)->linkExistingUser(
                $this->personnel->fresh(),
                (int) data_get($validated, 'manualLink.user_id')
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->personnel = $this->personnel->fresh(['position', 'structure']);
        $this->syncManualLinkSelection();
        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr_account.messages.manual_link_saved'));
    }

    public function getSnapshotProperty(): array
    {
        return app(MyHrAccountProvisioningService::class)->snapshot($this->personnel);
    }

    public function userOptions(): array
    {
        return app(MyHrAccountProvisioningService::class)->userOptions(
            $this->searchLinkedUser,
            $this->manualLink['user_id'] ? (int) $this->manualLink['user_id'] : null,
        );
    }

    protected function syncManualLinkSelection(): void
    {
        $this->manualLink['user_id'] = $this->snapshot['user']?->id;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.account-provisioning');
    }
}
