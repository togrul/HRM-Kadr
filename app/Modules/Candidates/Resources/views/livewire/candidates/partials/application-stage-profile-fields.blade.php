@if ($this->profileFieldDefinitions)
    @include('candidates::livewire.candidates.partials.application-stage-'.strtolower($this->currentPack()).'-fields')
@endif
