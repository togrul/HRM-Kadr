@include('candidates::livewire.candidates.partials.candidate-form-header')
@include('candidates::livewire.candidates.partials.candidate-form-core')
@include('candidates::livewire.candidates.partials.candidate-form-pack-fields')

<div class="flex items-end justify-between w-full">
    <x-modal-button>{{ __('candidates::common.labels.save') }}</x-modal-button>
</div>
