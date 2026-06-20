<x-modal-delete
    livewire-event-to-open-modal="deleteOrderWasSet"
    event-to-close-modal="orderWasDeleted"
    :modal-title="__('orders::order_form.titles.delete')"
    :modal-description="__('orders::order_list.messages.delete_order_confirm')"
    :modal-confirm-button-text="__('orders::order_form.actions.delete')"
    wire-click="deleteOrder"
/>
