<x-modal-delete
  livewire-event-to-open-modal="deleteLeaveWasSet"
  event-to-close-modal="leaveWasDeleted"
  :modal-title="__('Delete leave')"
  :modal-description="__('Are you sure you want to delete this data?')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deleteLeave"
/>
