<div class="flex flex-col">
    <div class="flex flex-col items-center justify-between rounded-xl bg-white px-2 py-2 sm:flex-row">
        <div class="flex items-center justify-center space-x-2">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('admin::references.buttons.add_approval_route') }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div wire:transition class="relative my-3 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 shadow-sm">
            <button class="absolute right-4 top-4 appearance-none" wire:click="closeCrud()">
                <x-icons.close-icon></x-icons.close-icon>
            </button>

            <div class="max-w-4xl space-y-5">
                <div class="space-y-2 pr-8">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('admin::references.menu.approval_routes') }}</x-ui.field-label>
                    <p class="text-sm leading-6 text-zinc-600">{{ __('admin::references.messages.approval_route_help') }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col">
                        <x-ui.select-dropdown
                            :label="__('admin::references.fields.request_type')"
                            placeholder="---"
                            mode="default"
                            class="w-full"
                            instance="approval-policy-request-type"
                            wire:model.live="form.request_type"
                            :model="$this->requestTypeOptions()"
                        />
                        @error('form.request_type')
                            <x-validation>{{ $message }}</x-validation>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" wire:model.live="form.include_primary_approver" class="mt-1 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400" />
                            <div>
                                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('admin::references.fields.include_primary_approver') }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('personnel::my_hr.hierarchy.messages.primary_policy_help') }}</p>
                            </div>
                        </div>
                    </label>

                    <label class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" wire:model.live="form.include_upper_approver" class="mt-1 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400" />
                            <div>
                                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('admin::references.fields.include_upper_approver') }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('personnel::my_hr.hierarchy.messages.upper_policy_help') }}</p>
                            </div>
                        </div>
                    </label>

                    <label class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" wire:model.live="form.hr_always_included" class="mt-1 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400" />
                            <div>
                                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('admin::references.fields.hr_always_included') }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('personnel::my_hr.hierarchy.messages.hr_policy_help') }}</p>
                            </div>
                        </div>
                    </label>

                    <label class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" wire:model.live="form.is_active" class="mt-1 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400" />
                            <div>
                                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('admin::references.fields.is_active') }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('personnel::my_hr.hierarchy.messages.policy_active_help') }}</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="flex justify-end">
                    <x-button mode="primary" wire:click.prevent="store">{{ __('admin::references.actions.save') }}</x-button>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-3 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($routes as $route)
                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('admin::references.fields.request_type') }}</x-ui.field-label>
                            <h3 class="mt-2 text-lg font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.requests.types.'.$route->request_type) }}</h3>
                        </div>
                        <button
                            wire:click.prevent="openCrud({{ $route->id ?: 'null' }}, '{{ $route->request_type }}')"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900"
                        >
                            <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                        </button>
                    </div>

                    <div class="mt-4 grid gap-3">
                        @foreach ([
                            ['label' => __('admin::references.fields.include_primary_approver'), 'value' => $route->include_primary_approver],
                            ['label' => __('admin::references.fields.include_upper_approver'), 'value' => $route->include_upper_approver],
                            ['label' => __('admin::references.fields.hr_always_included'), 'value' => $route->hr_always_included],
                            ['label' => __('admin::references.fields.is_active'), 'value' => $route->is_active],
                        ] as $row)
                            <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <span class="text-sm font-medium text-zinc-600">{{ $row['label'] }}</span>
                                <span @class([
                                    'inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200' => $row['value'],
                                    'bg-zinc-100 text-zinc-600 border border-zinc-200' => ! $row['value'],
                                ])>
                                    {{ $row['value'] ? __('onboarding-library::dashboard.values.yes') : __('onboarding-library::dashboard.values.no') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@include('includes.sweetalert-push')
