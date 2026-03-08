<div class="flex flex-col space-y-4">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {!! $title ?? '' !!}
        </h2>
    </div>

    <div
        class="flex w-full flex-col space-y-5 bg-white px-0 py-1"
        x-data="{ activeTab: 'sections' }"
    >
        <div class="inline-flex w-fit items-center rounded-2xl border border-zinc-200 bg-zinc-50 p-1">
            <button
                class="rounded-xl px-4 py-2 text-sm font-medium transition-all"
                :class="activeTab === 'sections' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700'"
                @click="activeTab = 'sections'"
            >
                {{ __('Sections') }}
            </button>
            <button
                class="rounded-xl px-4 py-2 text-sm font-medium transition-all"
                :class="activeTab === 'structures' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700'"
                @click="activeTab = 'structures'"
            >
                {{ __('Structures') }}
            </button>
        </div>

        <div
            x-show="activeTab == 'sections'"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-zinc-800">{{ __('Section permissions') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Select role access by module group. Descriptions remain visible for safer assignment decisions.') }}</p>
                </div>

                <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm">
                    <input wire:model.live="selectAll" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500" />
                    <span>{{ __('Select all') }}</span>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-4 2xl:grid-cols-3 xl:grid-cols-2">
                @foreach($permissions as $keyData => $permissionData)
                    <section
                        class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm"
                        wire:key="key-{{ $keyData }}"
                        x-data="{ open: false }"
                    >
                        <button
                            type="button"
                            class="flex w-full items-start justify-between gap-3 text-left"
                            @click="open = !open"
                            :aria-expanded="open"
                        >
                            <div class="min-w-0">
                                <h2 class="text-xs font-semibold uppercase tracking-tight font-mono text-zinc-800">{{ __($keyData) }}</h2>
                                <p class="mt-1 text-xs text-zinc-400">{{ __('Permission group') }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-blue-50 px-2 text-[11px] font-semibold text-blue-600">
                                    {{ count($permissionData) }}
                                </span>
                                <span
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 text-zinc-500 transition-transform"
                                    :class="{ 'rotate-180': open }"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </button>

                        <div
                            class="mt-3 border-t border-zinc-100 pt-3"
                            x-show="open"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                        >
                            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50/70">
                                @foreach($permissionData as $permission)
                                    <label
                                        for="permission_{{ $permission['id'] }}"
                                        class="grid grid-cols-[auto_1fr] gap-3 px-3 py-2.5 transition hover:bg-white {{ !$loop->last ? 'border-b border-zinc-200' : '' }}"
                                        wire:key="{{ $permission['id'] }}"
                                    >
                                        <div class="pt-0.5">
                                            <input
                                                wire:model="permissionList"
                                                value="{{ $permission['id'] }}"
                                                id="permission_{{ $permission['id'] }}"
                                                type="checkbox"
                                                class="h-5 w-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[12px] font-medium uppercase leading-5 text-zinc-800">{{ __($permission['title']) }}</span>
                                            </div>

                                            @if (! empty($permission['description']))
                                                <p class="mt-1 text-[12px] leading-5 text-zinc-500" title="{{ $permission['description'] }}">
                                                    {{ $permission['description'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <div
            x-show="activeTab == 'structures'"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-zinc-800">{{ __('Structure permissions') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Assign structure-scoped access without expanding the whole list at once.') }}</p>
                </div>

                <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm">
                    <input wire:model.live="selectAllStructure" type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500" />
                    <span>{{ __('Select all') }}</span>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                @foreach($structures as $structure)
                    <label
                        for="permission_{{ $structure->id }}_{{ $structure->shortname }}"
                        class="grid grid-cols-[auto_1fr_auto] gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50"
                        wire:key="{{ $structure->id }}_{{ $structure->shortname }}"
                    >
                        <div class="pt-0.5">
                            <input
                                wire:model="permissionStructureList"
                                value="{{ (int) $structure->id }}"
                                wire:change="updatePermissionStructureList({{ $structure->id }})"
                                id="permission_{{ $structure->id }}_{{ $structure->shortname }}"
                                type="checkbox"
                                class="h-5 w-5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                            />
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium leading-5 text-zinc-800">{{ $structure->name }}</p>
                            <p class="mt-1 text-xs text-blue-500">{{ $structure->shortname }}</p>
                        </div>

                        <span class="inline-flex h-6 items-center rounded-full bg-zinc-100 px-2 text-[11px] font-medium text-zinc-500">
                            {{ __('Structure') }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <x-modal-button>{{ __('Save permission') }}</x-modal-button>
</div>
