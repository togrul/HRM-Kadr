<div class="flex h-full min-h-0 flex-col overflow-hidden rounded-[18px] border border-zinc-200 bg-white">
    <div class="flex items-center justify-between border-b border-zinc-200 px-3 py-3">
        <h3 class="text-base font-medium tracking-tight text-zinc-600">
            {{ __('Structure') }}
        </h3>

        <button
            type="button"
            x-on:click="$dispatch('ui:sidebar-toggle')"
            class="inline-flex items-center justify-center rounded-md p-1 text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none"
            aria-label="Collapse sidebar"
            title="Collapse sidebar"
        >
            <x-icons.sidebar-toggle-icon size="w-5 h-5" color="text-zinc-700" hover="text-zinc-900" />
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-2 py-2">
        <x-tree.list>
            @foreach ($structures as $structure)
                <x-tree.item :model="$structure">{{ $structure->name }}</x-tree.item>
            @endforeach
        </x-tree.list>
    </div>

    <div class="mt-auto border-t border-zinc-200 bg-zinc-50 px-3 py-3">
        <a wire:navigate href="{{ route('admin.structures') }}" class="inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition-colors hover:text-zinc-700">
            <x-icons.settings-icon size="w-4 h-4" color="text-zinc-500" hover="text-zinc-700" />
            <span>{{ __('Manage hierarchy') }}</span>
        </a>
    </div>
</div>
