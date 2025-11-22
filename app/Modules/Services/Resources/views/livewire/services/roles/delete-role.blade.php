<x-modal-delete
  livewire-event-to-open-modal="deleteRoleWasSet"
  event-to-close-modal="roleWasDeleted"
  :modal-title="__('Delete role')"
  :modal-description="__('Are you sure you want to delete this role? This action cannot be undone.')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deleteRole"
/>