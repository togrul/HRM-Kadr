@php
    $linkSource = $this->snapshot['link']?->resolution_source;
    $linkSourceKey = $linkSource ? 'personnel::my_hr_account.link_sources.'.$linkSource : null;
    $linkSourceLabel = match ($linkSource) {
        'self_service_provisioned' => __('personnel::my_hr_account.link_sources.self_service_provisioned'),
        'manual_self_service_link' => __('personnel::my_hr_account.link_sources.manual_self_service_link'),
        default => $linkSource ? __('personnel::my_hr_account.messages.unknown_link_source') : '—',
    };
@endphp

<div class="flex flex-col gap-6 px-6 py-5">
    <div class="space-y-2">
        <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr_account.labels.kicker') }}</x-ui.field-label>
        <div class="flex flex-col gap-3 rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ $personnel->fullname }}</h2>
                <p class="text-sm text-zinc-500">
                    {{ $personnel->email ?: __('personnel::my_hr_account.messages.email_missing_short') }}
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr_account.labels.position') }}</x-ui.field-label>
                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-900">{{ $personnel->position?->name ?: '—' }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr_account.labels.structure') }}</x-ui.field-label>
                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-900">{{ $personnel->structure?->fullStructureName(includeRoot: true) ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    @error('provision')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror
    @error('manualLink.user_id')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr_account.labels.account_status') }}</x-ui.field-label>
                <div class="flex flex-wrap gap-2">
                    <x-notification.chip :mode="$this->snapshot['user'] ? 'emerald' : 'muted'">
                        {{ $this->snapshot['user'] ? __('personnel::my_hr_account.badges.account_exists') : __('personnel::my_hr_account.badges.account_missing') }}
                    </x-notification.chip>
                    <x-notification.chip :mode="$this->snapshot['can_provision'] ? 'sky' : 'amber'">
                        {{ $this->snapshot['can_provision'] ? __('personnel::my_hr_account.badges.email_ready') : __('personnel::my_hr_account.badges.email_missing') }}
                    </x-notification.chip>
                </div>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr_account.labels.user_name') }}</x-ui.field-label>
                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-900">{{ $this->snapshot['user']?->name ?: '—' }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr_account.labels.user_email') }}</x-ui.field-label>
                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-900">{{ $this->snapshot['user']?->email ?: '—' }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr_account.labels.invited_at') }}</x-ui.field-label>
                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-900">{{ optional($this->snapshot['user']?->self_service_invited_at)->format('d.m.Y H:i') ?: '—' }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::my_hr_account.labels.link_source') }}</x-ui.field-label>
                    <p class="mt-2 text-sm font-semibold tracking-tight text-zinc-900">
                        {{ $linkSourceLabel }}
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4 text-sm leading-6 text-zinc-600">
                {{ __('personnel::my_hr_account.messages.provisioning_hint') }}
            </div>

            <div class="mt-5 flex flex-wrap gap-3 border-t border-zinc-100 pt-4">
                <x-ui.async-button
                    wire:click="provision"
                    wire:target="provision"
                    variant="primary"
                >
                    {{ $this->snapshot['user'] ? __('personnel::my_hr_account.actions.regenerate_link') : __('personnel::my_hr_account.actions.create_account') }}
                </x-ui.async-button>
            </div>
        </div>

        <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr_account.labels.set_password_link') }}</x-ui.field-label>
                <p class="text-sm text-zinc-500">{{ __('personnel::my_hr_account.messages.set_password_help') }}</p>
            </div>

            @if ($resetUrl)
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold tracking-tight text-emerald-900">{{ __('personnel::my_hr_account.messages.reset_link_ready') }}</p>
                    <p class="mt-1 text-sm leading-6 text-emerald-700">{{ __('personnel::my_hr_account.messages.reset_link_ready_hint') }}</p>
                </div>
            @endif

            <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                @if ($resetUrl)
                    <textarea readonly rows="6" class="w-full resize-none rounded-2xl border border-zinc-200 bg-white px-3 py-3 text-sm text-zinc-700 focus:outline-none">{{ $resetUrl }}</textarea>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <button
                            type="button"
                            x-data
                            x-on:click="navigator.clipboard.writeText(@js($resetUrl)); window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: @js(__('personnel::my_hr_account.messages.reset_link_copied')) } }))"
                            class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900"
                        >
                            {{ __('personnel::my_hr_account.actions.copy_link') }}
                        </button>
                        <a
                            href="{{ $resetUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center rounded-2xl bg-zinc-950 px-4 py-2 text-sm font-semibold tracking-tight text-white transition hover:bg-zinc-800"
                        >
                            {{ __('personnel::my_hr_account.actions.open_link') }}
                        </a>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-white px-4 py-8 text-center text-sm text-zinc-500">
                        {{ __('personnel::my_hr_account.messages.reset_link_placeholder') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr_account.labels.manual_link') }}</x-ui.field-label>
            <p class="text-sm text-zinc-500">{{ __('personnel::my_hr_account.messages.manual_link_help') }}</p>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <x-ui.input-shell :label="__('personnel::my_hr_account.labels.user_picker')" labelClass="tracking-tight text-zinc-500">
                <x-ui.select-dropdown
                    :label="null"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    instance="my-hr-account-user-picker"
                    wire:model.live="manualLink.user_id"
                    :model="$this->userOptions()"
                    :selected-label="$this->snapshot['user'] ? trim(($this->snapshot['user']->name ?? '').' / '.($this->snapshot['user']->email ?? '')) : null"
                    search-model="searchLinkedUser"
                ></x-ui.select-dropdown>
            </x-ui.input-shell>

            <div class="flex flex-wrap gap-3">
                <x-ui.async-button
                    wire:click="saveManualLink"
                    wire:target="saveManualLink"
                    variant="secondary"
                >
                    {{ __('personnel::my_hr_account.actions.save_manual_link') }}
                </x-ui.async-button>
            </div>
        </div>
    </div>
</div>
