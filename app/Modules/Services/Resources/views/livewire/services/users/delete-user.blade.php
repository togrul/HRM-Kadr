<x-modal-delete
  livewire-event-to-open-modal="deleteUserWasSet"
  event-to-close-modal="userWasDeleted"
  :modal-title="__('Delete User')"
  :modal-description="__('Are you sure you want to delete this user? This action cannot be undone.')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deleteUser"
/>