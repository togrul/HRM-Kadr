<x-modal-delete
  livewire-event-to-open-modal="deletePermissionWasSet"
  event-to-close-modal="permissionWasDeleted"
  :modal-title="__('Delete permission')"
  :modal-description="__('Are you sure you want to delete this permission? This action cannot be undone.')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deletePermission"
/>