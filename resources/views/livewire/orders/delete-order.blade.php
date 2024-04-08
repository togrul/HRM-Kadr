<x-modal-delete
    livewire-event-to-open-modal="deleteOrderWasSet"
    event-to-close-modal="orderWasDeleted"
    :modal-title="__('Delete order')"
    :modal-description="__('Are you sure you want to delete this order?')"
    :modal-confirm-button-text="__('Delete')"
    wire-click="deleteOrder"
/>
