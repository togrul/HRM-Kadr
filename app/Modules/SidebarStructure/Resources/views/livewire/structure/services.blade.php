<div>
    @foreach ($this->sections as $section)
        <x-context-panel.item
            wire:key="services-section-{{ $section['key'] }}"
            wire:click.prevent="selectService('{{ $section['key'] }}')"
            :active="$selectedService === $section['key']"
            :count="$section['count']"
        >{{ $section['label'] }}</x-context-panel.item>
    @endforeach
</div>
