<div class="space-y-6">
    @php
        $emptyMessage = __('personnel::portfolio.messages.empty');
        $listSections = [
            'event_roles' => __('personnel::portfolio.analytics.event_roles'),
            'media_publishers' => __('personnel::portfolio.analytics.media_publishers'),
            'project_sponsors' => __('personnel::portfolio.analytics.project_sponsors'),
        ];
    @endphp

    <x-ui.filter-panel inner-class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-[minmax(14rem,1fr)_minmax(11rem,.75fr)_minmax(11rem,.75fr)_auto]">
        <x-ui.filter-field :label="__('personnel::portfolio.fields.status')">
            <x-ui.filter-native-select wire:model.live="statusFilter">
                <option value="all">Hamısı</option>
                @foreach (\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::mediaStatuses() as $status)
                    <option value="{{ $status }}">{{ __('personnel::portfolio.status.'.$status) }}</option>
                @endforeach
            </x-ui.filter-native-select>
        </x-ui.filter-field>
        <x-ui.filter-field :label="__('personnel::portfolio.fields.date_from')">
            <x-ui.filter-input wire:model.live="dateFrom" type="date" />
        </x-ui.filter-field>
        <x-ui.filter-field :label="__('personnel::portfolio.fields.date_to')">
            <x-ui.filter-input wire:model.live="dateTo" type="date" />
        </x-ui.filter-field>
        <div class="flex items-end">
            <x-ui.filter-reset-button wire:click="resetAnalyticsFilters" :label="__('personnel::portfolio.actions.reset_filters')">
                {{ __('personnel::common.actions.reset') }}
            </x-ui.filter-reset-button>
        </div>
    </x-ui.filter-panel>

    <div class="flex flex-wrap justify-end gap-2">
        <x-ui.async-button variant="secondary" size="sm" wire:click="exportExcel" wire:target="exportExcel" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_excel') }}</x-ui.async-button>
        <x-ui.async-button variant="secondary" size="sm" wire:click="exportCsv" wire:target="exportCsv" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_csv') }}</x-ui.async-button>
    </div>

    <x-surface-card :title="__('personnel::portfolio.tabs.analytics')" content-class="p-4">
        <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($this->analytics['cards'] as $card)
                <x-ui.metric-tile :label="$card['label']" :value="$card['value']" />
            @endforeach
        </div>
    </x-surface-card>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <x-surface-card :title="__('personnel::portfolio.analytics.yearly_activity')" content-class="p-0" clip>
            <div class="px-4 pb-4 pt-3">
                <p class="text-sm leading-6 text-zinc-500">{{ __('personnel::portfolio.description') }}</p>
            </div>
            <x-table.tbl
                :headers="[
                    __('personnel::portfolio.fields.start_date'),
                    __('personnel::portfolio.tabs.events'),
                    __('personnel::portfolio.tabs.media'),
                    __('personnel::portfolio.tabs.projects'),
                    __('personnel::portfolio.analytics.total_records'),
                ]"
            >
                @forelse ($this->analytics['yearly_activity'] as $row)
                    <tr>
                        <x-table.td><span class="font-semibold tracking-tight text-zinc-950">{{ $row['year'] }}</span></x-table.td>
                        <x-table.td>{{ $row['events'] }}</x-table.td>
                        <x-table.td>{{ $row['media'] }}</x-table.td>
                        <x-table.td>{{ $row['projects'] }}</x-table.td>
                        <x-table.td><span class="font-semibold tracking-tight text-zinc-950">{{ $row['total'] }}</span></x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-sm text-zinc-500">{{ $emptyMessage }}</td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </x-surface-card>

        <div class="space-y-4">
            <x-surface-card :title="__('personnel::portfolio.analytics.status_mix')" content-class="p-4">
                <div class="space-y-2">
                    @forelse ($this->analytics['status_mix'] as $row)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <span class="min-w-0 truncate text-sm font-semibold text-zinc-700">{{ $row['label'] }}</span>
                            <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                        </div>
                    @empty
                        <x-ui.empty-state :title="$emptyMessage" class="py-5" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('personnel::portfolio.analytics.registry_readiness')" content-class="p-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-ui.metric-tile :label="__('personnel::portfolio.analytics.event_clusters')" :value="$this->analytics['registry_readiness']['event_clusters']" class="px-3" />
                    <x-ui.metric-tile :label="__('personnel::portfolio.analytics.media_outlets')" :value="$this->analytics['registry_readiness']['media_outlets']" class="px-3" />
                    <x-ui.metric-tile :label="__('personnel::portfolio.analytics.project_clusters')" :value="$this->analytics['registry_readiness']['project_clusters']" class="px-3" />
                </div>
            </x-surface-card>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        @foreach ($listSections as $key => $label)
            <x-surface-card :title="$label" content-class="p-4">
                <div class="space-y-2">
                    @forelse ($this->analytics[$key] as $row)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <span class="min-w-0 truncate text-sm text-zinc-700">{{ $row['label'] }}</span>
                            <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                        </div>
                    @empty
                        <x-ui.empty-state :title="$emptyMessage" class="py-5" />
                    @endforelse
                </div>
            </x-surface-card>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-surface-card :title="__('personnel::portfolio.analytics.visibility_mix')" content-class="p-4">
            <div class="space-y-2">
                @forelse ($this->analytics['visibility_mix'] as $row)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                        <span class="min-w-0 truncate text-sm font-semibold text-zinc-700">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <x-ui.empty-state :title="$emptyMessage" class="py-5" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('personnel::portfolio.analytics.media_health_mix')" content-class="p-4">
            <div class="space-y-2">
                @forelse ($this->analytics['media_health_mix'] as $row)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                        <span class="min-w-0 truncate text-sm font-semibold text-zinc-700">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <x-ui.empty-state :title="$emptyMessage" class="py-5" />
                @endforelse
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-surface-card :title="__('personnel::portfolio.analytics.approval_backlog')" content-class="p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-ui.metric-tile :label="__('personnel::portfolio.tabs.events')" :value="$this->analytics['approval_backlog']['events']" />
                <x-ui.metric-tile :label="__('personnel::portfolio.tabs.media')" :value="$this->analytics['approval_backlog']['media']" />
                <x-ui.metric-tile :label="__('personnel::portfolio.tabs.projects')" :value="$this->analytics['approval_backlog']['projects']" />
            </div>
        </x-surface-card>

        <x-surface-card :title="__('personnel::portfolio.analytics.registry_masters')" content-class="p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-ui.metric-tile :label="__('personnel::portfolio.analytics.event_clusters')" :value="$this->analytics['registry_masters']['events']" />
                <x-ui.metric-tile :label="__('personnel::portfolio.analytics.media_outlets')" :value="$this->analytics['registry_masters']['media_outlets']" />
                <x-ui.metric-tile :label="__('personnel::portfolio.analytics.project_clusters')" :value="$this->analytics['registry_masters']['projects']" />
            </div>
        </x-surface-card>
    </div>
</div>
