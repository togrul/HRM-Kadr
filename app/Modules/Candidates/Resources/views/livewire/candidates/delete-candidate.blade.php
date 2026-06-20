<x-modal-delete
    livewire-event-to-open-modal="deleteCandidateWasSet"
    event-to-close-modal="candidateWasDeleted"
    :modal-title="__('candidates::common.titles.delete_candidate')"
    :modal-description="__('candidates::common.messages.delete_confirm')"
    :modal-confirm-button-text="__('candidates::common.actions.delete')"
    wire-click="deleteCandidate"
/>
