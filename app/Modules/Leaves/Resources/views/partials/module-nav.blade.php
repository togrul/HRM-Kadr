{{-- The Leaves module's own navigation in the context panel. Expects $active: 'leaves' | 'sick_certificates'. --}}
<x-context-panel.section :padded="true">
    <x-context-panel.item :href="route('leaves')" wire:navigate :active="$active === 'leaves'">
        {{ __('leaves::sick_certificates.nav.leaves') }}
    </x-context-panel.item>
    @can('viewAny', \App\Models\LeaveSickCertificate::class)
        <x-context-panel.item :href="route('leaves.sick-certificates')" wire:navigate :active="$active === 'sick_certificates'">
            {{ __('leaves::sick_certificates.nav.sick_certificates') }}
        </x-context-panel.item>
    @endcan
</x-context-panel.section>
