<x-modal-delete
    livewire-event-to-open-modal="deleteTemplateWasSet"
    event-to-close-modal="templateWasDeleted"
    :modal-title="__('orders::template_form.titles.delete_template')"
    :modal-description="__('orders::template_form.messages.delete_template_confirm')"
    :modal-confirm-button-text="__('orders::template_form.actions.delete')"
    wire-click="deleteTemplate"
/>
