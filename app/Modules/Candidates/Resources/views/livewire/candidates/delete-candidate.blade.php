<x-modal-delete
    livewire-event-to-open-modal="deleteCandidateWasSet"
    event-to-close-modal="candidateWasDeleted"
    :modal-title="__('Delete candidate')"
    :modal-description="__('Are you sure you want to delete this candidate?')"
    :modal-confirm-button-text="__('Delete')"
    wire-click="deleteCandidate"
/>
