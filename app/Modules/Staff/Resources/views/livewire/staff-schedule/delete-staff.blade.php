<x-modal-delete
  livewire-event-to-open-modal="deleteStaffWasSet"
  event-to-close-modal="staffWasDeleted"
  :modal-title="__('Delete staff')"
  :modal-description="__('Are you sure you want to delete this data?')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deleteStaff"
/>