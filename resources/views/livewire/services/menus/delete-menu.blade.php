<x-modal-delete
  livewire-event-to-open-modal="deleteMenuWasSet"
  event-to-close-modal="menuWasDeleted"
  :modal-title="__('Delete menu')"
  :modal-description="__('Are you sure you want to delete this menu? This action cannot be undone.')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deleteMenu"
/>