<x-modal-delete
    livewire-event-to-open-modal="deleteComponentWasSet"
    event-to-close-modal="componentWasDeleted"
    :modal-title="__('Delete component')"
    :modal-description="__('Are you sure you want to delete this component?')"
    :modal-confirm-button-text="__('Delete')"
    wire-click="deleteComponent"
/>

