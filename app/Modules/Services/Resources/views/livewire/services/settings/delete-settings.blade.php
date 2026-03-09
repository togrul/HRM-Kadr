<x-modal-delete
  livewire-event-to-open-modal="deleteSettingsWasSet"
  event-to-close-modal="settingsWasDeleted"
  :modal-title="__('services::settings.titles.delete')"
  :modal-description="__('services::settings.messages.delete_description')"
  :modal-confirm-button-text="__('services::common.actions.delete')"
  wire-click="deleteSetting"
/>
