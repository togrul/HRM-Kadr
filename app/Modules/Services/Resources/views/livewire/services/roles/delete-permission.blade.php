<x-modal-delete
  livewire-event-to-open-modal="deletePermissionWasSet"
  event-to-close-modal="permissionWasDeleted"
  :modal-title="__('services::roles.titles.delete_permission')"
  :modal-description="__('services::roles.messages.delete_permission_description')"
  :modal-confirm-button-text="__('services::common.actions.delete')"
  wire-click="deletePermission"
/>
