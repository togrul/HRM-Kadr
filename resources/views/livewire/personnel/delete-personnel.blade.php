<div>
    <x-modal-delete
        livewire-event-to-open-modal="deletePersonnelWasSet"
        event-to-close-modal="personnelWasDeleted"
        :modal-title="__('Delete personnel')"
        :modal-description="__('Are you sure you want to delete this personnel?')"
        :modal-confirm-button-text="__('Delete')"
        wire-click="deletePersonnel"
    />
</div>
