<div class="-mx-4 -my-4 flex min-h-full flex-col bg-white sm:-mx-8 sm:-my-8" x-data="{ activeTab: 'sections' }">
    <header class="border-b border-zinc-200 bg-white px-6 py-6 sm:px-10">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-tight text-zinc-500">{{ __('services::roles.permission_panel.eyebrow') }}</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950" id="slide-over-title">
                    {{ __('services::roles.permission_panel.title') }}
                </h2>
                <p class="mt-2 text-sm font-medium text-zinc-500">
                    {{ __('services::roles.permission_panel.subtitle') }}
                </p>
            </div>

            <span class="inline-flex shrink-0 items-center rounded-2xl bg-blue-50 px-4 py-2 text-sm font-bold text-blue-600">
                {{ __('services::roles.permission_panel.role_badge', ['role' => $this->roleDisplayName()]) }}
            </span>
        </div>
    </header>

    <main class="flex-1 space-y-6 bg-[#f5f5f7] px-6 py-6 pb-32 sm:px-10">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
            <label class="relative block">
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-zinc-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                    </svg>
                </span>
                <input
                    type="search"
                    wire:model.live.debounce.250ms="permissionSearch"
                    class="h-16 w-full rounded-2xl border border-zinc-300 bg-white pl-14 pr-5 text-base font-semibold text-zinc-950 shadow-sm outline-none transition placeholder:text-zinc-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    placeholder="{{ __('services::roles.actions.search_permission') }}"
                />
            </label>

            <label class="inline-flex h-14 items-center justify-between gap-3 rounded-2xl bg-white px-4 text-sm font-bold text-zinc-950 shadow-sm ring-1 ring-zinc-200">
                <input wire:model.live="selectAll" type="checkbox" class="peer sr-only" />
                <span class="relative inline-flex h-8 w-14 rounded-full bg-zinc-200 transition after:absolute after:left-1 after:top-1 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-blue-600 peer-checked:after:translate-x-6"></span>
                <span>{{ __('services::permissions.sections.select_all') }}</span>
            </label>
        </div>

        <div class="inline-flex w-fit rounded-2xl border border-zinc-200 bg-white p-1 shadow-sm">
            <button
                type="button"
                class="rounded-xl px-4 py-2 text-sm font-bold transition"
                :class="activeTab === 'sections' ? 'bg-zinc-950 text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-950'"
                @click="activeTab = 'sections'"
            >
                {{ __('services::permissions.tabs.sections') }}
            </button>
            <button
                type="button"
                class="rounded-xl px-4 py-2 text-sm font-bold transition"
                :class="activeTab === 'structures' ? 'bg-zinc-950 text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-950'"
                @click="activeTab = 'structures'"
            >
                {{ __('services::permissions.tabs.structures') }}
            </button>
        </div>

        <div x-show="activeTab === 'sections'" x-transition.opacity class="space-y-4">
            <p class="text-sm font-medium leading-6 text-zinc-700">
                {{ __('services::roles.permission_panel.help') }}
            </p>

            @forelse($permissions as $keyData => $permissionData)
                @php
                    $groupLabel = __($permissionData['translation_key']);
                    $resolvedGroupLabel = $groupLabel !== $permissionData['translation_key'] ? $groupLabel : $permissionData['fallback_label'];
                    $groupPermissionIds = collect($permissionData['permissions'])->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $selectedInGroup = count(array_intersect($groupPermissionIds, array_map('intval', $permissionList)));
                @endphp

                <section
                    class="rounded-2xl border border-zinc-300 bg-white shadow-sm"
                    wire:key="permission-group-{{ $keyData }}"
                    x-data="{ open: false }"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-5 text-left"
                        @click="open = !open"
                        :aria-expanded="open"
                    >
                        <div class="min-w-0">
                            <h3 class="text-sm font-black uppercase tracking-tight text-zinc-950">{{ $resolvedGroupLabel }}</h3>
                            <p class="mt-1 text-xs font-medium text-zinc-500">{{ __('services::permissions.sections.group') }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-blue-50 px-2 text-xs font-bold text-blue-600">
                                {{ $selectedInGroup ?: count($permissionData['permissions']) }}
                            </span>
                            <span
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition"
                                :class="open ? 'rotate-180 bg-blue-50 text-blue-600' : ''"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-180"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="border-t border-zinc-200 px-5 pb-5"
                    >
                        <div class="mt-4 space-y-3">
                            @foreach($permissionData['permissions'] as $permission)
                                @php
                                    $permissionLabel = __($permission['translation_key']);
                                    $resolvedPermissionLabel = $permissionLabel !== $permission['translation_key'] ? $permissionLabel : $permission['fallback_label'];
                                @endphp

                                <label
                                    for="permission_{{ $permission['id'] }}"
                                    class="grid cursor-pointer grid-cols-[auto_1fr] gap-3 rounded-xl border border-zinc-200 bg-[#f5f5f7] px-4 py-3 transition hover:border-zinc-300 hover:bg-white"
                                    wire:key="permission-row-{{ $permission['id'] }}"
                                >
                                    <input
                                        wire:model="permissionList"
                                        value="{{ $permission['id'] }}"
                                        id="permission_{{ $permission['id'] }}"
                                        type="checkbox"
                                        class="mt-1 h-5 w-5 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                    />

                                    <span class="min-w-0">
                                        <span class="block text-sm font-black uppercase tracking-tight text-zinc-950">{{ $resolvedPermissionLabel }}</span>
                                        @if (! empty($permission['description']))
                                            <span class="mt-1 block text-sm font-medium leading-5 text-zinc-600">{{ $permission['description'] }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('services::roles.permission_panel.no_permissions') }}</p>
                </div>
            @endforelse
        </div>

        <div x-show="activeTab === 'structures'" x-transition.opacity class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-300 bg-white px-5 py-4 shadow-sm">
                <div>
                    <p class="text-sm font-black uppercase tracking-tight text-zinc-950">{{ __('services::permissions.structures.title') }}</p>
                    <p class="mt-1 text-sm font-medium text-zinc-500">{{ __('services::permissions.structures.help') }}</p>
                </div>

                <label class="inline-flex h-12 items-center gap-3 rounded-2xl bg-[#f5f5f7] px-4 text-sm font-bold text-zinc-950">
                    <input wire:model.live="selectAllStructure" type="checkbox" class="h-5 w-5 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                    <span>{{ __('services::permissions.sections.select_all') }}</span>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                @foreach($structures as $structure)
                    <label
                        for="permission_{{ $structure->id }}_{{ $structure->shortname }}"
                        class="grid cursor-pointer grid-cols-[auto_1fr_auto] gap-3 rounded-2xl border border-zinc-300 bg-white px-4 py-3 shadow-sm transition hover:border-zinc-400 hover:bg-zinc-50"
                        wire:key="structure-permission-{{ $structure->id }}"
                    >
                        <input
                            wire:model="permissionStructureList"
                            value="{{ (int) $structure->id }}"
                            wire:change="updatePermissionStructureList({{ $structure->id }})"
                            id="permission_{{ $structure->id }}_{{ $structure->shortname }}"
                            type="checkbox"
                            class="mt-1 h-5 w-5 rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                        />

                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-zinc-950">{{ $structure->name }}</span>
                            <span class="mt-1 block truncate text-xs font-semibold text-blue-600">{{ $structure->shortname }}</span>
                        </span>

                        <span class="inline-flex h-7 items-center rounded-full bg-zinc-100 px-2 text-[11px] font-bold text-zinc-500">
                            {{ __('services::permissions.structures.badge') }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    </main>

    <footer class="sticky bottom-0 z-20 border-t border-zinc-800 bg-zinc-950 px-6 py-5 text-white sm:px-10">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-tight text-zinc-400">{{ __('services::roles.permission_panel.stats') }}</p>
                <p class="mt-1 text-xl font-black tracking-tight">
                    {{ __('services::roles.permission_panel.stats_value', ['selected' => $selectedPermissionCount, 'total' => $totalPermissionCount]) }}
                </p>
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="this.closest('[role=dialog]')?.querySelector('[x-ref=closeBtn]')?.click()"
                    class="h-12 rounded-xl border border-white/60 px-6 text-sm font-bold text-white transition hover:bg-white hover:text-zinc-950"
                >
                    {{ __('services::common.actions.cancel') }}
                </button>
                <button
                    type="button"
                    wire:click="store"
                    wire:loading.attr="disabled"
                    class="h-12 rounded-xl bg-white px-6 text-sm font-bold text-zinc-950 transition hover:bg-zinc-100 disabled:opacity-60"
                >
                    {{ __('services::common.actions.save') }}
                </button>
            </div>
        </div>
    </footer>
</div>
