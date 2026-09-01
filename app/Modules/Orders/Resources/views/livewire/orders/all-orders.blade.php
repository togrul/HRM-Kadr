@php
    $counts = $this->statusCounts;
    $statusDot = fn ($id): string => match ((int) $id) {
        10 => 'bg-[#f59e0b]',
        20 => 'bg-[#10b981]',
        30 => 'bg-[#f43f5e]',
        default => 'bg-[#a1a1aa]',
    };
    $isAdmin = auth()->user()?->hasRole('Admin');
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('orders::order_list.table.title')"
            :subtitle="number_format($counts['all'] ?? 0, 0, ',', ' ').' '.__('orders::order_list.table.unit')"
        >
            <x-context-panel.section>
                <x-context-panel.item
                    wire:click.prevent="setStatus('all')"
                    wire:loading.attr="disabled"
                    wire:target="setStatus"
                    :active="(string) $status === 'all'"
                    :dot="$statusDot(null)"
                    :count="number_format($counts['all'] ?? 0, 0, ',', ' ')"
                >{{ __('orders::order_list.filters.all') }}</x-context-panel.item>

                @foreach ($this->statuses as $_status)
                    <x-context-panel.item
                        wire:key="orders-panel-status-{{ $_status->id }}"
                        wire:click.prevent="setStatus({{ $_status->id }})"
                        wire:loading.attr="disabled"
                        wire:target="setStatus"
                        :active="(string) $status === (string) $_status->id"
                        :dot="$statusDot($_status->id)"
                        :count="number_format($counts[(int) $_status->id] ?? 0, 0, ',', ' ')"
                    >{{ $_status->name }}</x-context-panel.item>
                @endforeach

                @if ($isAdmin)
                    <x-context-panel.item
                        wire:click.prevent="setStatus('deleted')"
                        wire:loading.attr="disabled"
                        wire:target="setStatus"
                        :active="(string) $status === 'deleted'"
                        :dot="$statusDot(null)"
                        :count="number_format($counts['deleted'] ?? 0, 0, ',', ' ')"
                    >{{ __('orders::order_list.filters.deleted') }}</x-context-panel.item>
                @endif
            </x-context-panel.section>

            {{-- order types, counted inside the current scope --}}
            <x-context-panel.section :title="__('orders::order_list.filters.order_type')">
                @if ($selectedOrder)
                    <x-context-panel.item wire:click.prevent="selectOrder('')">
                        &larr; {{ __('orders::order_list.filters.show_all') }}
                    </x-context-panel.item>
                @endif

                @foreach ($this->typeFilters as $_type)
                    <x-context-panel.item
                        wire:key="orders-panel-type-{{ $_type['key'] }}"
                        wire:click.prevent="selectOrder('{{ $_type['key'] }}')"
                        wire:loading.attr="disabled"
                        wire:target="selectOrder"
                        :active="(string) $selectedOrder === $_type['key']"
                        :count="number_format($_type['count'], 0, ',', ' ')"
                    >{{ $_type['label'] }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-slot name="footer">
                <p class="text-[12px] font-semibold text-ink">{{ __('orders::order_list.guide.title') }}</p>
                <p class="mt-1 text-[11.5px] leading-snug text-ink-faint">{{ __('orders::order_list.guide.description') }}</p>
                <a href="{{ route('docs.guide', ['focus' => 'orders']) }}#orders-module"
                    class="mt-2 inline-flex items-center gap-1 text-[11.5px] font-semibold text-ink transition hover:underline">
                    {{ __('orders::order_list.actions.open_user_guide') }} &rarr;
                </a>
            </x-slot>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('orders::order_list.table.title')"
        :breadcrumb="__('orders::order_list.table.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="14" y2="17"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat
                :value="number_format($counts['all'] ?? 0, 0, ',', ' ')"
                :label="__('orders::order_list.table.unit')"
            />
            @foreach ($this->statuses as $_status)
                @continue (! in_array((int) $_status->id, [10, 30], true))
                <x-page-header.stat
                    :value="number_format($counts[(int) $_status->id] ?? 0, 0, ',', ' ')"
                    :label="$_status->name"
                    :tone="(int) $_status->id === 10 ? 'amber' : 'rose'"
                />
            @endforeach
        </x-slot:stats>

        <x-slot:actions>
            @can('edit-orders')
                <x-pill-button :href="route('orders.designer')" wire:navigate>
                    <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    {{ __('orders::order_composer.designer.title') }}
                </x-pill-button>
            @endcan
            @can('export-orders')
                <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel"
                    wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('orders::order_list.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
            @can('add-orders')
                <x-pill-button variant="primary" wire:click="openSideMenu('order-composer')">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('orders::order_composer.title') }}
                </x-pill-button>
            @endcan
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-end gap-3">
                <label class="w-full flex-1 sm:max-w-[360px]">
                    <span class="hrm-eyebrow block pb-1">{{ __('orders::order_list.filters.search') }}</span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search.order_no"
                            placeholder="{{ __('orders::order_list.filters.search_placeholder') }}"
                            class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                        />
                    </span>
                </label>

                <div class="shrink-0">
                    <span class="hrm-eyebrow block pb-1">{{ __('orders::order_list.filters.given_date') }}</span>
                    <div class="flex items-center gap-2">
                        <input
                            type="date"
                            wire:model.live="search.given_date.min"
                            aria-label="{{ __('orders::order_list.filters.date_start') }}"
                            class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0"
                        />
                        <span class="shrink-0 text-ink-faint">&ndash;</span>
                        <input
                            type="date"
                            wire:model.live="search.given_date.max"
                            aria-label="{{ __('orders::order_list.filters.date_end') }}"
                            class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0"
                        />
                    </div>
                </div>

                <x-pill-button wire:click="resetFilter" class="!h-[34px]">{{ __('orders::order_list.filters.reset') }}</x-pill-button>
            </div>

            <p class="text-[11.5px] text-ink-faint">{{ __('orders::order_list.hints.docx_only') }}</p>
        </div>
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl :headers="$this->getTableHeaders()">
        @forelse ($this->orders as $_order)
            @php
                $isDocx = $_order->template_render_mode === \App\Services\Orders\Document\OrderIssueService::RENDER_MODE_DOCX;
            @endphp
            <tr wire:key="order-row-{{ $_order->id }}" @class([
                'bg-[#fffbeb]/60' => (int) $_order->status_id === 10,
                'bg-[#fff1f2]/60' => (int) $_order->status_id === 30,
            ])>
                <x-table.td>
                    <span class="hrm-num text-[13px] font-semibold text-ink">{{ $_order->order_no }}</span>
                </x-table.td>

                <x-table.td>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-flex items-center rounded-lg bg-ink px-2.5 py-1 text-[11px] font-semibold uppercase tracking-tight text-white">
                            {{ $_order->order?->name ?? (data_get($_order->template_snapshot, 'label') ?? '—') }}
                        </span>
                        @if ($_order->orderType)
                            <svg class="h-3.5 w-3.5 shrink-0 text-ink-faint/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            <span class="inline-flex items-center rounded-lg border border-hairline bg-[#fafafa] px-2 py-1 text-[11px] font-medium uppercase text-ink-muted">{{ $_order->orderType->name }}</span>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col leading-tight">
                        <span class="hrm-num text-[13px] font-medium text-ink-soft">{{ \Carbon\Carbon::parse($_order->given_date)->format('d.m.Y') }}</span>
                        @if ($isAdmin && $status == 'deleted')
                            <span class="text-[11px] text-ink-faint">{{ __('orders::order_list.table.deleted_date') }}: {{ \Carbon\Carbon::parse($_order->deleted_at)->format('d.m.Y H:i') }}</span>
                            <span class="text-[11px] text-ink-faint">{{ __('orders::order_list.table.deleted_by') }}: {{ $_order->personDidDelete?->name ?? '—' }}</span>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex items-center gap-2.5">
                        <x-avatar :name="$_order->given_by" />
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $_order->given_by }}</p>
                            @if ($_order->given_by_rank)
                                <p class="truncate text-[11px] text-ink-faint">{{ $_order->given_by_rank }}</p>
                            @endif
                        </div>
                    </div>
                </x-table.td>

                <x-table.td>
                    <x-status design="modern" :status-id="$_order->status_color_id" :label="$_order->status->name" />
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        {{-- a soft-deleted order must be restored before it can be printed or transitioned --}}
                        @if ($isDocx && $status != 'deleted')
                            @can('export-orders')
                                <button wire:click="printOrder('{{ $_order->order_no }}')"
                                    title="{{ __('orders::order_list.actions.download_now') }}" aria-label="{{ __('orders::order_list.actions.download_now') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-teal-50 hover:text-teal-600">
                                    <x-icons.print-file color="text-current" hover="text-current" />
                                </button>
                            @endcan
                            @can('add-orders')
                                @if ($_order->status_id == 10)
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_composer.actions.approve')), message: @js(__('orders::order_composer.confirm.approve')), confirmText: @js(__('orders::order_composer.actions.approve')), tone: 'emerald', run: () => $wire.approveOrder('{{ $_order->order_no }}') })"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg bg-emerald-600 px-2.5 text-[12px] font-semibold text-white transition hover:bg-emerald-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        {{ __('orders::order_composer.actions.approve') }}
                                    </button>
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_composer.actions.cancel')), message: @js(__('orders::order_composer.confirm.cancel_pending')), confirmText: @js(__('orders::order_composer.actions.cancel')), tone: 'rose', run: () => $wire.cancelOrder('{{ $_order->order_no }}') })"
                                        title="{{ __('orders::order_composer.actions.cancel') }}" aria-label="{{ __('orders::order_composer.actions.cancel') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    </button>
                                @elseif ($_order->status_id == 20)
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_composer.actions.revert')), message: @js(__('orders::order_composer.confirm.revert')), confirmText: @js(__('orders::order_composer.actions.revert')), tone: 'amber', run: () => $wire.revertOrder('{{ $_order->order_no }}') })"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-hairline px-2.5 text-[12px] font-medium text-ink-muted transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                        {{ __('orders::order_composer.actions.revert') }}
                                    </button>
                                @elseif ($_order->status_id == 30)
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_composer.actions.reopen')), message: @js(__('orders::order_composer.confirm.reopen')), confirmText: @js(__('orders::order_composer.actions.reopen')), tone: 'teal', run: () => $wire.reopenOrder('{{ $_order->order_no }}') })"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-hairline px-2.5 text-[12px] font-medium text-ink-muted transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                        {{ __('orders::order_composer.actions.reopen') }}
                                    </button>
                                @endif
                            @endcan
                        @endif

                        @if ($status == 'deleted')
                            @can('edit-orders')
                                <button wire:click="restoreData('{{ $_order->order_no }}')" title="{{ __('orders::order_list.actions.restore') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-teal-50 hover:text-teal-600">
                                    <x-icons.recover color="text-current" hover="text-current" />
                                </button>
                            @endcan
                            @can('delete-orders')
                                <button type="button"
                                    x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_list.actions.force_delete')), message: @js(__('orders::order_list.messages.force_delete_confirm')), confirmText: @js(__('orders::order_list.actions.force_delete')), tone: 'rose', run: () => $wire.forceDeleteData('{{ $_order->order_no }}') })"
                                    title="{{ __('orders::order_list.actions.force_delete') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                    <x-icons.force-delete />
                                </button>
                            @endcan
                        @else
                            @if ($isDocx && $_order->status_id == 10)
                                @can('add-orders')
                                    <button type="button" wire:click="openSideMenu('order-composer', {{ $_order->id }})"
                                        title="{{ __('orders::order_composer.actions.edit') }}" aria-label="{{ __('orders::order_composer.actions.edit') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                        <x-icons.document-icon />
                                    </button>
                                @endcan
                            @endif
                            @can('delete-orders')
                                <button wire:click="setDeleteOrder('{{ $_order->order_no }}')" title="{{ __('orders::order_list.actions.delete') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600">
                                    <x-icons.delete-icon />
                                </button>
                            @endcan
                        @endif
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" />
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->orders" :unit="__('orders::order_list.table.unit')" />

    @can('add-orders')
        <x-side-modal size="xx-large">
            @if ($showSideMenu === 'order-composer')
                <livewire:orders.order-composer :orderId="$modelName ? (int) $modelName : null"
                    :key="'order-composer-' . ($modelName ?? 'new')" />
            @endif
        </x-side-modal>
    @endcan

    @can('delete-orders')
        <div>
            <livewire:orders.delete-order wire:key="order-delete-modal" />
        </div>
    @endcan
</div>
