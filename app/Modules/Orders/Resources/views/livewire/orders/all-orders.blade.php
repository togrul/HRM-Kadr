@php
    $counts = $this->statusCounts;
    $statusDot = fn ($id): string => match ((int) $id) {
        10 => 'bg-[#f59e0b]',
        20 => 'bg-[#10b981]',
        30 => 'bg-[#f43f5e]',
        default => 'bg-[#a1a1aa]',
    };
    $isAdmin = auth()->user()?->hasRole('Admin');
    // Confirm-modal payload for component tags: the js directive inside an x-tag attribute breaks the
    // compiler, so the dispatch expression is built here with Js::from() and echoed.
    $confirm = fn (string $title, string $message, string $confirmText, string $tone, string $method, string $orderNo): string => sprintf(
        "\$dispatch('confirm-action', { title: %s, message: %s, confirmText: %s, tone: '%s', run: () => \$wire.%s(%s) })",
        \Illuminate\Support\Js::from($title), \Illuminate\Support\Js::from($message), \Illuminate\Support\Js::from($confirmText), $tone, $method, \Illuminate\Support\Js::from($orderNo),
    );
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
        collapsible-filters
        :filters-active="$this->hasActiveFilters"
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
            @can('export-orders')
                <x-pill-button :icon="true" wire:click.prevent="exportExcel"
                    wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('orders::order_list.actions.export_excel') }}" aria-label="{{ __('orders::order_list.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
            @can('edit-orders')
                <x-ui.row-menu :label="__('orders::order_list.actions.more')">
                    <x-ui.row-menu.item :href="route('orders.designer')" wire:navigate>{{ __('orders::order_composer.designer.title') }}</x-ui.row-menu.item>
                </x-ui.row-menu>
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
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('orders::order_list.filters.search') }}</span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search.order_no"
                            placeholder="{{ __('orders::order_list.filters.search_placeholder') }}"
                            class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-base sm:text-sm text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                        />
                    </span>
                </label>

                <div class="shrink-0">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('orders::order_list.filters.given_date') }}</span>
                    <div class="flex items-center gap-2">
                        <input
                            type="date"
                            wire:model.live="search.given_date.min"
                            aria-label="{{ __('orders::order_list.filters.date_start') }}"
                            class="hrm-num h-10 w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base sm:text-sm text-ink focus:border-ink focus:bg-white focus:ring-0"
                        />
                        <span class="shrink-0 text-ink-faint">&ndash;</span>
                        <input
                            type="date"
                            wire:model.live="search.given_date.max"
                            aria-label="{{ __('orders::order_list.filters.date_end') }}"
                            class="hrm-num h-10 w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base sm:text-sm text-ink focus:border-ink focus:bg-white focus:ring-0"
                        />
                    </div>
                </div>

                <x-filter.reset :active="$this->hasActiveFilters" />
            </div>

            <p class="text-[11.5px] text-ink-faint">{{ __('orders::order_list.hints.docx_only') }}</p>
        </div>
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl sticky :headers="$this->getTableHeaders()">
        @forelse ($this->orders as $_order)
            @php
                $isDocx = $_order->template_render_mode === \App\Modules\Orders\Infrastructure\Document\OrderIssueService::RENDER_MODE_DOCX;
                $inTrash = $status == 'deleted';
                $isDraft = $isDocx && \App\Modules\Orders\Infrastructure\Document\OrderIssueService::isDraft($_order);
                $statusId = (int) $_order->status_id;
                $isPending = $isDocx && ! $inTrash && $statusId === 10;
                $isApproved = $isDocx && ! $inTrash && $statusId === 20;
                $isCancelled = $isDocx && ! $inTrash && $statusId === 30;
            @endphp
            <tr wire:key="order-row-{{ $_order->id }}" wire:click="openSideMenu('order-preview', {{ $_order->id }})" @class([
                'cursor-pointer transition hover:bg-[#fafafa]',
                'bg-[#fffbeb]/60' => $statusId === 10 && ! $isDraft,
                'bg-[#fff1f2]/60' => $statusId === 30,
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
                        @if ($isAdmin && $inTrash)
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
                    <x-status design="modern" :status-id="$_order->status_color_id" :label="$_order->status_label" />
                </x-table.td>

                <x-table.td :isButton="true">
                    {{-- clicks here must not bubble to the row (which opens the preview) --}}
                    <div class="flex items-center justify-end gap-1" x-on:click.stop>
                        {{-- one status-driven primary action; everything else lives in the menu --}}
                        @if ($isPending && $isDraft)
                            @can('add-orders')
                                <x-pill-button wire:click="openSideMenu('order-composer', {{ $_order->id }})">{{ __('orders::order_list.actions.continue') }}</x-pill-button>
                            @endcan
                        @elseif ($isPending)
                            @can('add-orders')
                                <x-pill-button x-on:click="{{ $confirm(__('orders::order_composer.actions.approve'), __('orders::order_composer.confirm.approve'), __('orders::order_composer.actions.approve'), 'emerald', 'approveOrder', $_order->order_no) }}">{{ __('orders::order_composer.actions.approve') }}</x-pill-button>
                            @endcan
                        @elseif ($isApproved)
                            @can('export-orders')
                                <x-pill-button wire:click="printOrder('{{ $_order->order_no }}')"><x-icons.print-file color="text-current" hover="text-current" size="h-4 w-4" />{{ __('orders::order_list.actions.download') }}</x-pill-button>
                            @endcan
                        @endif

                        <x-ui.row-menu>
                            <x-ui.row-menu.item wire:click="openSideMenu('order-preview', {{ $_order->id }})">{{ __('orders::order_list.actions.preview') }}</x-ui.row-menu.item>

                            @if ($inTrash)
                                @can('edit-orders')
                                    <x-ui.row-menu.item wire:click="restoreData('{{ $_order->order_no }}')"><x-icons.recover color="text-current" hover="text-current" size="h-4 w-4" />{{ __('orders::order_list.actions.restore') }}</x-ui.row-menu.item>
                                @endcan
                                @can('delete-orders')
                                    <x-ui.row-menu.separator />
                                    <x-ui.row-menu.item danger x-on:click="{{ $confirm(__('orders::order_list.actions.force_delete'), __('orders::order_list.messages.force_delete_confirm'), __('orders::order_list.actions.force_delete'), 'rose', 'forceDeleteData', $_order->order_no) }}"><x-icons.force-delete color="text-current" hover="text-current" size="h-4 w-4" />{{ __('orders::order_list.actions.force_delete') }}</x-ui.row-menu.item>
                                @endcan
                            @else
                                @if ($isDocx && ! $isApproved && ! $isDraft)
                                    @can('export-orders')
                                        <x-ui.row-menu.item wire:click="printOrder('{{ $_order->order_no }}')"><x-icons.print-file color="text-current" hover="text-current" size="h-4 w-4" />{{ __('orders::order_list.actions.download') }}</x-ui.row-menu.item>
                                    @endcan
                                @endif
                                @can('add-orders')
                                    @if ($isPending)
                                        <x-ui.row-menu.item wire:click="openSideMenu('order-composer', {{ $_order->id }})">{{ __('orders::order_list.actions.edit') }}</x-ui.row-menu.item>
                                    @endif
                                    @if ($isDocx)
                                        <x-ui.row-menu.item wire:click="duplicateOrder('{{ $_order->order_no }}')">{{ __('orders::order_list.actions.duplicate') }}</x-ui.row-menu.item>
                                    @endif
                                    @if ($isApproved)
                                        <x-ui.row-menu.item x-on:click="{{ $confirm(__('orders::order_composer.actions.revert'), __('orders::order_composer.confirm.revert'), __('orders::order_composer.actions.revert'), 'amber', 'revertOrder', $_order->order_no) }}">{{ __('orders::order_composer.actions.revert') }}</x-ui.row-menu.item>
                                    @endif
                                    @if ($isCancelled)
                                        <x-ui.row-menu.item x-on:click="{{ $confirm(__('orders::order_composer.actions.reopen'), __('orders::order_composer.confirm.reopen'), __('orders::order_composer.actions.reopen'), 'teal', 'reopenOrder', $_order->order_no) }}">{{ __('orders::order_composer.actions.reopen') }}</x-ui.row-menu.item>
                                    @endif
                                    @if ($isPending || $isApproved)
                                        <x-ui.row-menu.item x-on:click="{{ $confirm(__('orders::order_composer.actions.cancel'), $isApproved ? __('orders::order_composer.confirm.cancel_approved') : __('orders::order_composer.confirm.cancel_pending'), __('orders::order_composer.actions.cancel'), 'rose', 'cancelOrder', $_order->order_no) }}">{{ __('orders::order_composer.actions.cancel') }}</x-ui.row-menu.item>
                                    @endif
                                @endcan
                                @can('delete-orders')
                                    <x-ui.row-menu.separator />
                                    <x-ui.row-menu.item danger x-on:click="{{ $confirm(__('orders::order_list.actions.delete'), __('orders::order_list.messages.delete_order_confirm'), __('orders::order_list.actions.delete'), 'rose', 'deleteOrder', $_order->order_no) }}"><x-icons.delete-icon color="text-current" hover="text-current" size="h-4 w-4" />{{ __('orders::order_list.actions.delete') }}</x-ui.row-menu.item>
                                @endcan
                            @endif
                        </x-ui.row-menu>
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" :filtered="$this->hasActiveFilters">
                <x-slot:action>
                    @can('add-orders')
                        <x-pill-button variant="primary" wire:click="openSideMenu('order-composer')">{{ __('orders::order_composer.title') }}</x-pill-button>
                    @endcan
                </x-slot:action>
            </x-table.empty>
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->orders" :unit="__('orders::order_list.table.unit')" />

    <x-side-modal size="xx-large">
        @if ($showSideMenu === 'order-composer')
            @can('add-orders')
                <livewire:orders.order-composer :orderId="$modelName ? (int) $modelName : null" :presetCode="$secondModel ?? ''"
                    :key="'order-composer-' . ($modelName ?? 'new') . '-' . ($secondModel ?? 'any')" />
            @endcan
        @elseif ($showSideMenu === 'order-preview' && $modelName)
            <livewire:orders.order-preview :orderId="(int) $modelName" :key="'order-preview-' . $modelName" />
        @endif
    </x-side-modal>
</div>
