<x-modal-delete
  livewire-event-to-open-modal="deleteSettingsWasSet"
  event-to-close-modal="settingsWasDeleted"
  :modal-title="__('Delete setting')"
  :modal-description="__('Are you sure you want to delete this setting? This action cannot be undone.')"
  :modal-confirm-button-text="__('Delete')"
  wire-click="deleteSetting"
/>