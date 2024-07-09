<x-modal-delete
    livewire-event-to-open-modal="deleteRankWasSet"
    event-to-close-modal="rankWasDeleted"
    :modal-title="__('Delete rank')"
    :modal-description="__('Are you sure you want to delete this data? This action cannot be undone.')"
    :modal-confirm-button-text="__('Delete')"
    wire-click="deleteRank"
/>
