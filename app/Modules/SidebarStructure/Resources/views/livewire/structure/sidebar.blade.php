{{-- Renders as a plain section of the contextual panel card, not as its own card. --}}
<div class="flex min-h-0 flex-col bg-white">
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
