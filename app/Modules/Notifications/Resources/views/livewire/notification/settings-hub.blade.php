<div class="space-y-5">
    <x-surface-card :title="__('notifications::common.titles.module')" icon="icons.notification-icon">
        <div class="space-y-4">
            <div class="rounded-[1.7rem] border border-zinc-200 bg-[linear-gradient(180deg,rgba(250,250,250,0.96),rgba(244,244,245,0.78))] p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)]">
                <div class="space-y-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('notifications::common.titles.module') }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <h3 class="text-xl font-semibold tracking-tight text-zinc-950">{{ data_get($tabs, $activeTab.'.label', __('notifications::common.tabs.'.$activeTab)) }}</h3>
                            <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-[11px] font-semibold text-zinc-500">{{ __('notifications::common.badges.active_tab') }}</span>
                        </div>
                    </div>

                    <div class="-mx-1 flex flex-wrap items-center justify-start gap-2">
                        @foreach ($tabs as $tabKey => $tab)
                            <button
                                type="button"
                                wire:click="selectTab('{{ $tabKey }}')"
                                aria-current="{{ $activeTab === $tabKey ? 'page' : 'false' }}"
                                class="{{ $activeTab === $tabKey ? 'border-zinc-950 bg-zinc-950 text-white shadow-[0_12px_28px_rgba(15,23,42,0.18)] ring-4 ring-zinc-950/5' : 'border-zinc-200 bg-white/90 text-zinc-600 hover:-translate-y-0.5 hover:border-zinc-300 hover:bg-white hover:text-zinc-900 hover:shadow-[0_10px_24px_rgba(15,23,42,0.07)]' }} inline-flex items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-semibold transition duration-200"
                            >
                                <span>{{ $tab['label'] }}</span>
                                @if (isset($tab['count']))
                                    <span class="{{ $activeTab === $tabKey ? 'border-white/20 bg-white/10 text-white' : 'border-zinc-200 bg-zinc-50 text-zinc-500' }} rounded-full border px-2 py-0.5 text-[11px] font-semibold">
                                        {{ $tab['count'] }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-zinc-200 bg-zinc-50/70 p-3 shadow-[0_18px_48px_rgba(15,23,42,0.05)]" wire:key="notification-settings-panel-{{ $activeTab }}">
                @if ($activeTab === 'overview')
                    <livewire:notification.overview-panel :key="'notification-overview-panel'" />
                @elseif ($activeTab === 'analytics')
                    <livewire:notification.analytics-panel :key="'notification-analytics-panel-tab'" lazy />
                @elseif ($activeTab === 'history')
                    <livewire:notification.history-board :key="'notification-history-board-tab'" lazy />
                @elseif ($activeTab === 'approval')
                    <livewire:notification.approval-queue :key="'notification-approval-queue-tab'" lazy />
                @elseif ($activeTab === 'announcements')
                    <livewire:notification.announcement-composer :key="'notification-announcement-composer-tab'" lazy />
                @elseif ($activeTab === 'templates')
                    <livewire:notification.template-manager :key="'notification-template-manager-tab'" lazy />
                @elseif ($activeTab === 'rules')
                    <livewire:notification.rule-manager :key="'notification-rule-manager-tab'" lazy />
                @elseif ($activeTab === 'campaigns')
                    <livewire:notification.campaign-board :key="'notification-campaign-board-tab'" lazy />
                @endif
            </div>
        </div>
    </x-surface-card>
</div>
