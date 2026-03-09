<x-modal-delete
    livewire-event-to-open-modal="deleteComponentWasSet"
    event-to-close-modal="componentWasDeleted"
    :modal-title="__('services::components.titles.delete')"
    :modal-description="__('services::components.messages.delete_description')"
    :modal-confirm-button-text="__('services::common.actions.delete')"
    wire-click="deleteComponent"
/>
