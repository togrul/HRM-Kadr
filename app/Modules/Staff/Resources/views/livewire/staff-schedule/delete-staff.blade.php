<x-modal-delete
  livewire-event-to-open-modal="deleteStaffWasSet"
  event-to-close-modal="staffWasDeleted"
  :modal-title="__('staff::common.titles.delete_staff')"
  :modal-description="__('staff::common.messages.delete_confirm')"
  :modal-confirm-button-text="__('staff::common.actions.delete')"
  wire-click="deleteStaff"
/>
