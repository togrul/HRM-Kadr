<?php

namespace App\Modules\Admin\Support\Traits\Admin;

/**
 * Admin CRUD feedback, routed through the app-wide toast (`notify` → <x-notification>)
 * and the global confirm modal (`confirm-action` → <x-confirm-modal>). The "Swal" names
 * are kept so the admin components calling them stay untouched.
 */
trait CallSwalTrait
{
    public function callSuccessSwal(): void
    {
        $this->dispatch('notify', type: 'success', message: __('admin::common.alerts.success.text'));
    }

    public function callWarningSwal(): void
    {
        $this->dispatch('notify', type: 'error', message: __('admin::common.alerts.warning.text'));
    }

    public function callDeletedSwal(): void
    {
        $this->dispatch('notify', type: 'success', message: __('ui::common.messages.record_deleted'));
    }

    /**
     * Opens the global confirm modal; on confirm it calls this component's `delete()`.
     * A closure cannot cross the wire, so the modal resolves the method by component id.
     */
    public function callDeletePromptSwal(): void
    {
        $this->dispatch(
            'confirm-action',
            title: __('admin::common.alerts.delete_prompt.title'),
            message: __('admin::common.alerts.delete_prompt.text'),
            confirmText: __('ui::common.swal.yes_delete_it'),
            tone: 'rose',
            wireId: $this->getId(),
            method: 'delete',
        );
    }
}
