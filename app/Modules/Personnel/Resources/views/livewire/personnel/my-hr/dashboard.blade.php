<div class="flex flex-col px-6 py-6 space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('ui::menu.items.my_hr') }}</x-ui.field-label>
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.title') }}</h1>
                <p class="max-w-3xl text-sm text-zinc-500">{{ __('personnel::my_hr.description') }}</p>
            </div>

            <a href="{{ route('docs.guide', ['focus' => 'my-hr']) }}#my-hr-module" class="inline-flex items-center rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">
                {{ __('personnel::my_hr.actions.open_docs') }}
            </a>
        </div>
    </div>

    @if (! $this->hasPersonnelLink)
        <div class="rounded-[28px] border border-amber-200 bg-amber-50/80 p-6 shadow-sm">
            <div class="max-w-3xl space-y-3">
                <x-ui.field-label as="div" class="tracking-tight text-amber-700">{{ __('personnel::my_hr.empty_state.kicker') }}</x-ui.field-label>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.empty_state.title') }}</h2>
                <p class="text-sm leading-6 text-zinc-600">{{ __('personnel::my_hr.empty_state.body') }}</p>
                <div class="rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm text-zinc-700">
                    {{ __('personnel::my_hr.empty_state.hint') }}
                </div>
            </div>
        </div>
    @else
        <div class="rounded-[28px] border border-zinc-200 bg-zinc-50/60 p-5 shadow-sm">
            <div class="flex flex-wrap gap-2">
                @foreach ($this->tabs() as $tab)
                    @php
                        $tabLabelKey = str_replace('-', '_', $tab);
                    @endphp
                    <button
                        type="button"
                        wire:click="setActiveTab('{{ $tab }}')"
                        wire:loading.attr="disabled"
                        wire:target="setActiveTab"
                        class="{{ $activeTab === $tab ? 'bg-zinc-950 text-white shadow-sm' : 'border border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50' }} rounded-2xl px-4 py-2.5 text-sm font-semibold tracking-tight transition"
                    >
                        {{ __('personnel::my_hr.tabs.'.$tabLabelKey) }}
                    </button>
                @endforeach
            </div>

            <div class="mt-6">
                @if ($activeTab === 'overview')
                    <livewire:personnel.my-hr.summary :personnel-id="$personnelId" :key="'my-hr-summary-'.$personnelId" />
                @elseif ($activeTab === 'requests')
                    <livewire:personnel.my-hr.requests :personnel-id="$personnelId" :key="'my-hr-requests-'.$personnelId" />
                @elseif ($activeTab === 'notifications')
                    <livewire:personnel.my-hr.notifications :personnel-id="$personnelId" :key="'my-hr-notifications-'.$personnelId" />
                @elseif ($activeTab === 'onboarding')
                    <livewire:personnel.my-hr.onboarding :personnel-id="$personnelId" :key="'my-hr-onboarding-'.$personnelId" />
                @elseif ($activeTab === 'development-plan')
                    <livewire:personnel.my-hr.development-plan :personnel-id="$personnelId" :key="'my-hr-development-plan-'.$personnelId" />
                @elseif ($activeTab === 'learning')
                    <livewire:personnel.my-hr.learning :personnel-id="$personnelId" :key="'my-hr-learning-'.$personnelId" />
                @elseif ($activeTab === 'documents')
                    <livewire:personnel.my-hr.documents :personnel-id="$personnelId" :key="'my-hr-documents-'.$personnelId" />
                @elseif ($activeTab === 'hierarchy')
                    <livewire:personnel.my-hr.hierarchy :personnel-id="$personnelId" :key="'my-hr-hierarchy-'.$personnelId" />
                @else
                    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="space-y-3">
                            @php
                                $activeTabLabelKey = str_replace('-', '_', $activeTab);
                            @endphp
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::my_hr.tabs.'.$activeTabLabelKey) }}</x-ui.field-label>
                            <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.messages.foundation_title') }}</h2>
                            <p class="max-w-3xl text-sm leading-6 text-zinc-600">{{ __('personnel::my_hr.messages.foundation_body', ['tab' => __('personnel::my_hr.tabs.'.$activeTabLabelKey)]) }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
