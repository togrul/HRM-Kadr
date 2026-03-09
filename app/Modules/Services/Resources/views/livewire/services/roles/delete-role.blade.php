<x-modal-delete
  livewire-event-to-open-modal="deleteRoleWasSet"
  event-to-close-modal="roleWasDeleted"
  :modal-title="__('services::roles.titles.delete_role')"
  :modal-description="__('services::roles.messages.delete_role_description')"
  :modal-confirm-button-text="__('services::common.actions.delete')"
  wire-click="deleteRole"
/>
