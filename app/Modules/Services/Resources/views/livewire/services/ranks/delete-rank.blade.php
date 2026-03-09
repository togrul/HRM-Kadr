<x-modal-delete
    livewire-event-to-open-modal="deleteRankWasSet"
    event-to-close-modal="rankWasDeleted"
    :modal-title="__('services::ranks.titles.delete')"
    :modal-description="__('services::ranks.messages.delete_description')"
    :modal-confirm-button-text="__('services::common.actions.delete')"
    wire-click="deleteRank"
/>
