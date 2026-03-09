<x-modal-delete
  livewire-event-to-open-modal="deleteLeaveWasSet"
  event-to-close-modal="leaveWasDeleted"
  :modal-title="__('leaves::common.titles.delete_leave')"
  :modal-description="__('leaves::common.messages.delete_confirm')"
  :modal-confirm-button-text="__('leaves::common.actions.delete')"
  wire-click="deleteLeave"
/>
