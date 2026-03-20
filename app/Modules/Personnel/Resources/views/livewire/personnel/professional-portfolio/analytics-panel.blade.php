<div class="space-y-6">
    <div class="grid gap-1 lg:grid-cols-3 xl:grid-cols-4">
        <x-ui.input-shell :label="__('personnel::portfolio.fields.status')" labelClass="tracking-tight text-zinc-500">
            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-2 text-sm text-zinc-800 focus:border-zinc-300 focus:outline-none">
                <option value="all">Hamısı</option>
                @foreach (\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioOptions::mediaStatuses() as $status)
                    <option value="{{ $status }}">{{ __('personnel::portfolio.status.'.$status) }}</option>
                @endforeach
            </select>
        </x-ui.input-shell>
        <x-ui.input-shell :label="__('personnel::portfolio.fields.date_from')" labelClass="tracking-tight text-zinc-500">
            <input wire:model.live="dateFrom" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" />
        </x-ui.input-shell>
        <x-ui.input-shell :label="__('personnel::portfolio.fields.date_to')" labelClass="tracking-tight text-zinc-500">
            <input wire:model.live="dateTo" type="date" class="w-full rounded-2xl border border-zinc-200 bg-white px-2 py-1.5 text-[13px] text-zinc-800 focus:border-zinc-300 focus:outline-none" />
        </x-ui.input-shell>
    </div>

    <div class="flex justify-end gap-2">
        <x-ui.async-button variant="secondary" size="sm" wire:click="exportExcel" wire:target="exportExcel" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_excel') }}</x-ui.async-button>
        <x-ui.async-button variant="secondary" size="sm" wire:click="exportCsv" wire:target="exportCsv" wire:loading.attr="disabled">{{ __('personnel::portfolio.actions.export_csv') }}</x-ui.async-button>
    </div>

    <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
        @foreach ($this->analytics['cards'] as $card)
            <div class="rounded-[22px] border border-zinc-200 bg-white px-4 py-4 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight">{{ $card['label'] }}</x-ui.field-label>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.yearly_activity') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('personnel::portfolio.description') }}</p>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-[20px] border border-zinc-200">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50">
                        <tr class="text-left text-zinc-500">
                            <th class="px-4 py-3 font-semibold">{{ __('personnel::portfolio.fields.start_date') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('personnel::portfolio.tabs.events') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('personnel::portfolio.tabs.media') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('personnel::portfolio.tabs.projects') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('personnel::portfolio.analytics.total_records') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white text-zinc-700">
                        @forelse ($this->analytics['yearly_activity'] as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold tracking-tight text-zinc-950">{{ $row['year'] }}</td>
                                <td class="px-4 py-3">{{ $row['events'] }}</td>
                                <td class="px-4 py-3">{{ $row['media'] }}</td>
                                <td class="px-4 py-3">{{ $row['projects'] }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $row['total'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.status_mix') }}</p>
                <div class="mt-4 space-y-2">
                    @forelse ($this->analytics['status_mix'] as $row)
                        <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <span class="text-sm font-semibold text-zinc-700">{{ $row['label'] }}</span>
                            <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-6 text-sm text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.registry_readiness') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                        <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.analytics.event_clusters') }}</x-ui.field-label>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['registry_readiness']['event_clusters'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                        <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.analytics.media_outlets') }}</x-ui.field-label>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['registry_readiness']['media_outlets'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                        <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.analytics.project_clusters') }}</x-ui.field-label>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['registry_readiness']['project_clusters'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        @php
            $sections = [
                'event_roles' => __('personnel::portfolio.analytics.event_roles'),
                'media_publishers' => __('personnel::portfolio.analytics.media_publishers'),
                'project_sponsors' => __('personnel::portfolio.analytics.project_sponsors'),
            ];
        @endphp
        @foreach ($sections as $key => $label)
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ $label }}</p>
                <div class="mt-4 space-y-2">
                    @forelse ($this->analytics[$key] as $row)
                        <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <span class="max-w-[80%] truncate text-sm text-zinc-700">{{ $row['label'] }}</span>
                            <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-6 text-sm text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.visibility_mix') }}</p>
            <div class="mt-4 space-y-2">
                @forelse ($this->analytics['visibility_mix'] as $row)
                    <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <span class="text-sm font-semibold text-zinc-700">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-6 text-sm text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.media_health_mix') }}</p>
            <div class="mt-4 space-y-2">
                @forelse ($this->analytics['media_health_mix'] as $row)
                    <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <span class="text-sm font-semibold text-zinc-700">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-6 text-sm text-zinc-500">{{ __('personnel::portfolio.messages.empty') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.approval_backlog') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.tabs.events') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['approval_backlog']['events'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.tabs.media') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['approval_backlog']['media'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.tabs.projects') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['approval_backlog']['projects'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold tracking-tight text-zinc-950">{{ __('personnel::portfolio.analytics.registry_masters') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.analytics.event_clusters') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['registry_masters']['events'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.analytics.media_outlets') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['registry_masters']['media_outlets'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.analytics.project_clusters') }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $this->analytics['registry_masters']['projects'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
