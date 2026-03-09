<x-modal-delete
  livewire-event-to-open-modal="deleteUserWasSet"
  event-to-close-modal="userWasDeleted"
  :modal-title="__('services::users.titles.delete')"
  :modal-description="__('services::users.messages.delete_description')"
  :modal-confirm-button-text="__('services::common.actions.delete')"
  wire-click="deleteUser"
/>
