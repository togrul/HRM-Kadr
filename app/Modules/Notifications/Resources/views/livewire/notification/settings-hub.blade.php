{{-- One card: section chips on top, the active panel straight under them. The old layout
     wrapped a gradient header card and a shadowed panel card inside the titled card. --}}
<div class="space-y-4">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <x-filter.nav wrap class="min-w-0">
                    @foreach ($tabs as $tabKey => $tab)
                        <x-filter.item wire:click.prevent="selectTab('{{ $tabKey }}')" :active="$activeTab === $tabKey" wire:key="notification-hub-tab-{{ $tabKey }}">
                            <span>{{ $tab['label'] }}</span>
                            @if (isset($tab['count']))
                                <span @class([
                                    'hrm-num ml-1.5 rounded-full px-1.5 text-[11px]',
                                    'bg-white/15 text-white' => $activeTab === $tabKey,
                                    'bg-white text-ink-muted' => $activeTab !== $tabKey,
                                ])>{{ $tab['count'] }}</span>
                            @endif
                        </x-filter.item>
                    @endforeach
                </x-filter.nav>

                <a href="{{ route('docs.guide', ['focus' => 'notifications']) }}" class="inline-flex h-9 shrink-0 items-center gap-1 text-[12.5px] font-medium text-ink-muted transition hover:text-ink">
                    {{ __('notifications::common.buttons.open_docs') }}
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>

            <div wire:key="notification-settings-panel-{{ $activeTab }}">
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
</div>
