<x-modal-delete
    livewire-event-to-open-modal="deleteTemplateWasSet"
    event-to-close-modal="templateWasDeleted"
    :modal-title="__('Delete template')"
    :modal-description="__('Are you sure you want to delete this template?')"
    :modal-confirm-button-text="__('Delete')"
    wire-click="deleteTemplate"
/>
