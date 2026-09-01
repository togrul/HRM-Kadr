<div class="space-y-5" x-data wire:key="roles">
    <section class="space-y-2">
        <h1 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::roles.dashboard.title') }}</h1>
        <p class="max-w-2xl text-[12.5px] leading-5 text-ink-faint">{{ __('services::roles.dashboard.subtitle') }}</p>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @foreach ($roles as $role)
            @php
                $isAdminRole = str_contains(Str::lower($role->name), 'admin');
                $roleDisplayName = $this->roleDisplayName($role);
                $initials = $role->users
                    ->take(2)
                    ->map(function ($user) {
                        $source = trim((string) ($user->name ?: $user->email));

                        return Str::of($source)
                            ->replaceMatches('/\s+/', ' ')
                            ->explode(' ')
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
                            ->implode('');
                    })
                    ->filter()
                    ->values();
            @endphp

            <article
                wire:key="role-card-{{ $role->id }}"
                class="group relative flex min-h-[200px] flex-col rounded-xl border border-hairline bg-white p-5 transition hover:border-zinc-300"
            >
                @if ($isAdminRole)
                    <span class="absolute left-0 top-0 z-20 inline-flex h-6 items-center rounded-br-md rounded-tl-xl bg-[#f4f4f5] px-2.5 text-[10px] font-semibold uppercase tracking-[0.06em] text-ink-muted">
                        {{ __('services::roles.badges.default') }}
                    </span>
                @endif

                @unless ($isUpdate && (int) $role_id === (int) $role->id)
                    <button
                        type="button"
                        wire:click.prevent="openSideMenu('set-permission', {{ $role->id }})"
                        class="absolute inset-0 z-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-ink/20"
                        aria-label="{{ __('services::roles.actions.manage_role_permissions', ['role' => $roleDisplayName]) }}"
                    ></button>
                @endunless

                <div class="relative grid h-11 grid-cols-[2.75rem_1fr_auto] items-start gap-3">
                    <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] border border-hairline bg-[#fafafa] text-ink-muted">
                        <x-icons.shield-icon size="h-5 w-5" color="text-current" hover="text-current" />
                    </div>

                    <div></div>

                    <div class="relative z-20 flex shrink-0 items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click.stop.prevent="editRole({{ $role->id }})"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink"
                            title="{{ __('services::common.actions.edit') }}"
                            aria-label="{{ __('services::common.actions.edit') }}"
                        >
                            <x-icons.edit-icon size="h-[17px] w-[17px]" color="text-current" hover="text-current" />
                        </button>

                        <button
                            type="button"
                            wire:click.stop.prevent="setDeleteRole({{ $role->id }})"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600"
                            title="{{ __('services::common.actions.delete') }}"
                            aria-label="{{ __('services::common.actions.delete') }}"
                        >
                            <x-icons.delete-icon size="h-[17px] w-[17px]" color="text-current" hover="text-current" />
                        </button>
                    </div>
                </div>

                <div class="relative mt-9 flex-1">
                    @if ($isUpdate && (int) $role_id === (int) $role->id)
                        <div class="relative z-30 space-y-3 rounded-2xl border border-zinc-200 bg-[#f7f7f8] p-3 shadow-sm" wire:key="role-edit-form-{{ $role->id }}">
                            <div class="flex items-center justify-between gap-2">
                                <label for="role_name_{{ $role->id }}" class="text-xs font-bold uppercase tracking-tight text-zinc-500">
                                    {{ __('services::common.labels.role') }}
                                </label>
                                <button
                                    type="button"
                                    wire:click.prevent="cancel"
                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-zinc-500 shadow-sm ring-1 ring-zinc-200 transition hover:text-zinc-950"
                                    aria-label="{{ __('services::common.actions.cancel') }}"
                                    title="{{ __('services::common.actions.cancel') }}"
                                >
                                    <x-icons.close-icon size="h-3.5 w-3.5" color="text-zinc-500" hover="text-zinc-950" />
                                </button>
                            </div>
                            <input
                                id="role_name_{{ $role->id }}"
                                type="text"
                                wire:model="role_name"
                                class="h-10 w-full min-w-0 truncate rounded-xl border border-zinc-200 bg-white px-3 text-[13px] font-semibold text-zinc-950 shadow-sm outline-none transition focus:border-zinc-400 focus:ring-0"
                                autofocus
                            />
                            @error('role_name')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click.prevent="store"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-950 text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2"
                                    aria-label="{{ __('services::common.actions.save') }}"
                                    title="{{ __('services::common.actions.save') }}"
                                >
                                    <x-icons.check-simple-icon size="h-4 w-4" color="text-white" hover="text-white" />
                                </button>
                                <button
                                    type="button"
                                    wire:click.prevent="cancel"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-zinc-300 focus:ring-offset-2"
                                    aria-label="{{ __('services::common.actions.cancel') }}"
                                    title="{{ __('services::common.actions.cancel') }}"
                                >
                                    <x-icons.close-icon size="h-4 w-4" color="text-zinc-500" hover="text-zinc-950" />
                                </button>
                            </div>
                        </div>
                    @else
                        <h2 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ $roleDisplayName }}</h2>
                        <p class="mt-1.5 max-w-[18rem] text-[12px] leading-5 text-ink-faint">
                            {{ $isAdminRole ? __('services::roles.dashboard.admin_description') : __('services::roles.dashboard.role_description') }}
                        </p>
                    @endif
                </div>

                <div class="relative mt-6 border-t border-hairline-subtle pt-4">
                    <div class="flex items-center justify-between gap-3">
                        <button
                            type="button"
                            wire:click.prevent="openSideMenu('set-permission', {{ $role->id }})"
                            class="relative z-20 text-[12.5px] font-semibold tracking-[-0.01em] text-ink transition hover:text-ink-muted"
                        >
                            {{ trans_choice('services::roles.dashboard.permission_count', (int) $role->permissions_count, ['count' => (int) $role->permissions_count]) }}
                        </button>

                        <div class="flex min-w-0 items-center justify-end">
                            @foreach ($initials as $index => $initial)
                                <span
                                    class="-ml-2 inline-flex h-7 w-7 items-center justify-center rounded-full border-2 border-white text-[10px] font-bold {{ $index === 0 ? 'bg-zinc-200 text-zinc-700' : 'bg-zinc-950 text-white' }}"
                                    title="{{ __('services::roles.dashboard.assigned_users') }}"
                                >
                                    {{ $initial }}
                                </span>
                            @endforeach

                            @if ($role->users_count > $initials->count())
                                <span class="-ml-2 inline-flex h-7 min-w-7 items-center justify-center rounded-full border-2 border-white bg-zinc-100 px-2 text-[10px] font-bold text-zinc-600">
                                    +{{ $role->users_count - $initials->count() }}
                                </span>
                            @elseif ($role->users_count === 0)
                                <span class="truncate text-[11.5px] text-ink-faint">{{ __('services::roles.dashboard.no_users') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @endforeach

        <article
            class="flex min-h-[200px] flex-col justify-center rounded-xl border border-dashed border-hairline bg-[#fafafa] p-5 text-center transition hover:border-zinc-300 hover:bg-white"
            wire:key="role-create-card"
        >
            @if ($isCreating)
                <form wire:submit.prevent="store" class="relative z-30 mx-auto w-full max-w-[15rem] space-y-3 rounded-2xl bg-white p-3 text-left shadow-sm ring-1 ring-zinc-200">
                    <div>
                        <label for="role_name_new" class="text-xs font-bold uppercase tracking-tight text-zinc-500">
                            {{ __('services::common.labels.role') }}
                        </label>
                        <input
                            id="role_name_new"
                            type="text"
                            wire:model="role_name"
                            class="mt-2 h-10 w-full min-w-0 rounded-xl border border-zinc-200 bg-white px-3 text-[13px] font-semibold text-zinc-950 outline-none transition focus:border-zinc-400 focus:ring-0"
                            autofocus
                        />
                        @error('role_name')
                            <x-validation>{{ $message }}</x-validation>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-950 text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2"
                            aria-label="{{ __('services::roles.actions.create_role') }}"
                            title="{{ __('services::roles.actions.create_role') }}"
                        >
                            <x-icons.check-simple-icon size="h-4 w-4" color="text-white" hover="text-white" />
                        </button>
                        <button
                            type="button"
                            wire:click.prevent="cancel"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-zinc-300 focus:ring-offset-2"
                            aria-label="{{ __('services::common.actions.cancel') }}"
                            title="{{ __('services::common.actions.cancel') }}"
                        >
                            <x-icons.close-icon size="h-4 w-4" color="text-zinc-500" hover="text-zinc-950" />
                        </button>
                    </div>
                </form>
            @else
                <button
                    type="button"
                    wire:click.prevent="startCreate"
                    class="flex h-full min-h-[150px] flex-col items-center justify-center gap-3 rounded-xl text-ink-muted transition hover:text-ink focus:outline-none focus:ring-2 focus:ring-ink/20"
                >
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-current">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                        </svg>
                    </span>
                    <span class="text-[12.5px] font-semibold">{{ __('services::roles.actions.create_role') }}</span>
                </button>
            @endif
        </article>
    </section>

    <x-side-modal size="x-large">
        @if ($showSideMenu == 'set-permission')
            <livewire:services.roles.set-permission :roleModel="$modelName" :key="'services-role-permission-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>

    <div>
        @auth
            <livewire:services.roles.delete-role wire:key="services-role-delete-modal" />
        @endauth
    </div>
</div>
