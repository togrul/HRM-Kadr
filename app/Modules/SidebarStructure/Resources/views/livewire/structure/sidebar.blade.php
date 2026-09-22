{{-- Renders as a plain section of the contextual panel card, not as its own card. --}}
{{--
    Selection is client-side: a click moves the highlight here and hands the id to the host
    (`selectStructure`, same payload the server dispatch used to send), with no sidebar
    round-trip. $wire.selectedStructure is set deferred, so it rides along on the next real
    request and the host-driven reset (filterSelected) still clears it.
    Folding: roots open, deeper levels collapsed, except the path down to the selection.
--}}
<div class="flex min-h-0 flex-col bg-white"
    x-data="{
        open: @js($openIds),
        isOpen(id) { return this.open[id] === true },
        toggle(id) { this.open[id] = ! this.isOpen(id) },
        sel(id) { return this.$wire.selectedStructure === id },
        pick(id) {
            this.$wire.selectedStructure = id;
            Livewire.dispatch('selectStructure', [id]);
        },
    }">
    <p class="hrm-eyebrow border-t border-hairline-subtle px-3.5 pb-1 pt-3">
        {{ __('structure::common.titles.structure') }}
    </p>

    <div class="px-1.5 pb-2">
        <x-tree.list>
            @foreach ($structures as $structure)
                <x-tree.item :model="$structure">{{ $structure->name }}</x-tree.item>
            @endforeach
        </x-tree.list>
    </div>

    <div class="mt-auto border-t border-hairline-subtle bg-[#fafafa] px-3.5 py-2.5">
        <a wire:navigate href="{{ route('admin.structures') }}" class="inline-flex items-center gap-2 text-[12px] font-medium text-ink-muted transition-colors hover:text-ink">
            <x-icons.settings-icon size="w-4 h-4" color="text-zinc-500" hover="text-zinc-700" />
            <span>{{ __('structure::common.actions.manage_hierarchy') }}</span>
        </a>
    </div>
</div>
