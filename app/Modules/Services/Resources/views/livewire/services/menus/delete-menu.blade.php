<x-modal-delete
  livewire-event-to-open-modal="deleteMenuWasSet"
  event-to-close-modal="menuWasDeleted"
  :modal-title="__('services::menus.titles.delete')"
  :modal-description="__('services::menus.messages.delete_description')"
  :modal-confirm-button-text="__('services::common.actions.delete')"
  wire-click="deleteMenu"
/>
