<div
    class="flex flex-col"
    x-data
    x-init="
        const applyPaginatorTheme = (isUpdate = false) => {
            const paginator = document.querySelector('span[aria-current=page]>span');
            if (!paginator) return;
            paginator.classList.remove('bg-blue-50', 'text-blue-600', 'bg-green-100', 'text-green-600');
            paginator.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
        };

        applyPaginatorTheme(false);

        const currentComponentId = @js($this->getId());
        window.__ordersPaginatorHooks ??= {};

        if (currentComponentId && !window.__ordersPaginatorHooks[currentComponentId]) {
            window.__ordersPaginatorHooks[currentComponentId] = true;

            Livewire.hook('message.processed', (message, component) => {
                if (!component || component.id !== currentComponentId) return;

                const payload = message?.updateQueue?.[0]?.payload ?? {};
                const name = message?.updateQueue?.[0]?.name;
                const methods = ['gotoPage', 'previousPage', 'nextPage', 'selectOrder'];
                const events = ['openSideMenu', 'closeSideMenu', 'orderAdded', 'selectOrder', 'orderWasDeleted'];

                if (methods.includes(payload.method) || events.includes(payload.event) || name === 'search') {
                    applyPaginatorTheme(true);
                }
            });
        }
    "
>
    {{-- sidebar --}}
    <x-slot name="sidebar">
        <livewire:structure.orders wire:key="orders-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    @php
        $isActive = fn ($value) => (string) $status === (string) $value;
        $pill = 'inline-flex h-8 shrink-0 items-center whitespace-nowrap rounded-lg px-3 text-[13px] font-medium transition';
        $pillOn = 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200';
        $pillOff = 'text-zinc-500 hover:text-zinc-900';
    @endphp

    <div class="flex flex-col gap-4 px-4 py-5 sm:px-6">

        {{-- ===================== Premium toolbar + filters ===================== --}}
        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            {{-- header --}}
            <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-800 to-black text-white shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="14" y2="17"/></svg>
                    </div>
                    <div class="leading-tight">
                        <h1 class="text-[16px] font-semibold tracking-tight text-zinc-900">{{ __('orders::order_list.table.title') }}</h1>
                        <p class="text-[12px] text-zinc-400">{{ $this->orders->total() }} {{ __('orders::order_list.filters.all') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @can('add-orders')
                        <button type="button" wire:click="openSideMenu('order-composer')"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-zinc-900 px-4 text-[13px] font-semibold text-white shadow-sm transition hover:bg-zinc-700 active:scale-[0.98]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            {{ __('orders::order_composer.title') }}
                        </button>
                    @endcan
                    @can('edit-orders')
                        <a href="{{ route('orders.designer') }}" wire:navigate
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-[13px] font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">
                            <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            {{ __('orders::order_composer.designer.title') }}
                        </a>
                    @endcan
                    @can('export-orders')
                        <button wire:click.prevent="exportExcel" type="button" title="Excel"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white transition hover:border-emerald-300 hover:bg-emerald-50">
                            <x-icons.excel-icon />
                        </button>
                    @endcan
                </div>
            </div>

            {{-- guide strip --}}
            <a href="{{ route('docs.guide', ['focus' => 'orders']) }}#orders-module"
                class="flex items-center gap-2 border-y border-zinc-100 bg-zinc-50/60 px-4 py-2 text-[12px] text-zinc-500 transition hover:bg-zinc-50 sm:px-5">
                <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <span class="font-medium text-zinc-600">{{ __('orders::order_list.guide.title') }}</span>
                <span class="hidden truncate sm:inline">{{ __('orders::order_list.guide.description') }}</span>
                <span class="ml-auto shrink-0 font-medium text-zinc-700">{{ __('orders::order_list.actions.open_user_guide') }} →</span>
            </a>

            {{-- filters --}}
            <div class="space-y-3 px-4 py-3.5 sm:px-5">
                {{-- status: one scrollable line --}}
                <div class="flex items-center gap-1 overflow-x-auto rounded-xl bg-zinc-100/80 p-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button type="button" wire:click.prevent="setStatus('all')"
                        class="{{ $pill }} {{ $isActive('all') ? $pillOn : $pillOff }}">{{ __('orders::order_list.filters.all') }}</button>
                    @foreach ($this->statuses as $_status)
                        <button type="button" wire:click.prevent="setStatus({{ $_status->id }})"
                            class="{{ $pill }} {{ $isActive($_status->id) ? $pillOn : $pillOff }}">{{ $_status->name }}</button>
                    @endforeach
                    @role('Admin')
                        <button type="button" wire:click.prevent="setStatus('deleted')"
                            class="{{ $pill }} {{ $isActive('deleted') ? $pillOn : $pillOff }}">{{ __('orders::order_list.filters.deleted') }}</button>
                    @endrole
                </div>

                {{-- search + date range + reset (default input components, one row) --}}
                <div class="flex items-end gap-3 overflow-x-auto">
                    <div class="min-w-[160px] flex-1">
                        <x-label for="search.order_no">{{ __('orders::order_list.filters.search') }}</x-label>
                        <x-livewire-input mode="gray" name="search.order_no"
                            wire:model.live.debounce.400ms="search.order_no"
                            placeholder="{{ __('orders::order_list.filters.search') }}" />
                    </div>

                    <div class="shrink-0">
                        <x-label for="search.given_date.min">{{ __('orders::order_list.filters.given_date') }}</x-label>
                        <div class="flex items-center gap-2">
                            <div class="w-32">
                                <x-pikaday-input mode="gray" name="search.given_date.min" format="Y-MM-DD" wire:model.live="search.given_date.min" placeholder="başlanğıc">
                                    <x-slot name="script">$el.onchange = function () { @this.set('search.given_date.min', $el.value); }</x-slot>
                                </x-pikaday-input>
                            </div>
                            <span class="shrink-0 text-zinc-400">–</span>
                            <div class="w-32">
                                <x-pikaday-input mode="gray" name="search.given_date.max" format="Y-MM-DD" wire:model.live="search.given_date.max" placeholder="bitmə">
                                    <x-slot name="script">$el.onchange = function () { @this.set('search.given_date.max', $el.value); }</x-slot>
                                </x-pikaday-input>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <x-button mode="black" wire:click="resetFilter">{{ __('orders::order_list.filters.reset') }}</x-button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== Default table component (premium body) ===================== --}}
        <x-table.tbl :headers="$this->getTableHeaders()" title="{{ __('orders::order_list.table.title') }}">
            @forelse ($this->orders as $_order)
                <tr wire:key="order-row-{{ $_order->id }}" class="{{ $_order->status_id == 30 ? 'bg-rose-50/40' : '' }}">
                    <x-table.td>
                        <span class="font-mono text-[12px] text-zinc-400">{{ $_order->row_no }}</span>
                    </x-table.td>

                    <x-table.td>
                        <span class="font-mono text-[13px] font-semibold text-zinc-900">{{ $_order->order_no }}</span>
                    </x-table.td>

                    <x-table.td>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center rounded-lg bg-zinc-900 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-tight text-white">
                                {{ $_order->order?->name ?? (data_get($_order->template_snapshot, 'label') ?? '—') }}
                            </span>
                            @if ($_order->orderType)
                                <svg class="h-3.5 w-3.5 shrink-0 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                <span class="inline-flex items-center rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1 text-[11px] font-medium uppercase text-zinc-500">{{ $_order->orderType->name }}</span>
                            @endif
                        </div>
                    </x-table.td>

                    <x-table.td>
                        <div class="flex flex-col leading-tight">
                            <span class="text-[13px] font-medium text-zinc-700">{{ \Carbon\Carbon::parse($_order->given_date)->format('d.m.Y') }}</span>
                            @role('Admin')
                                @if ($status == 'deleted')
                                    <span class="text-[11px] text-zinc-400">{{ __('orders::order_list.table.deleted_date') }}: {{ \Carbon\Carbon::parse($_order->deleted_at)->format('d.m.Y H:i') }}</span>
                                    <span class="text-[11px] text-zinc-400">{{ __('orders::order_list.table.deleted_by') }}: {{ $_order->personDidDelete?->name ?? '—' }}</span>
                                @endif
                            @endrole
                        </div>
                    </x-table.td>

                    <x-table.td>
                        <div class="flex flex-col leading-tight">
                            <span class="text-[13px] font-medium text-zinc-900">{{ $_order->given_by }}</span>
                            @if ($_order->given_by_rank)
                                <span class="text-[11px] text-zinc-400">{{ $_order->given_by_rank }}</span>
                            @endif
                        </div>
                    </x-table.td>

                    <x-table.td>
                        <x-status design="modern" :status-id="$_order->status_color_id" :label="$_order->status->name"></x-status>
                    </x-table.td>

                    <x-table.td :isButton="true">
                        <div class="flex items-center justify-end gap-1">
                            @if ($_order->template_render_mode === \App\Services\Orders\Document\OrderIssueService::RENDER_MODE_DOCX)
                                @can('export-orders')
                                    <button wire:click="printOrder('{{ $_order->order_no }}')"
                                        title="{{ __('orders::order_list.actions.download_now') }}" aria-label="{{ __('orders::order_list.actions.download_now') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-teal-50 hover:text-teal-600">
                                        <x-icons.print-file color="text-current" hover="text-current"></x-icons.print-file>
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
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        </button>
                                    @elseif ($_order->status_id == 20)
                                        <button type="button"
                                            x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_composer.actions.revert')), message: @js(__('orders::order_composer.confirm.revert')), confirmText: @js(__('orders::order_composer.actions.revert')), tone: 'amber', run: () => $wire.revertOrder('{{ $_order->order_no }}') })"
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-zinc-200 px-2.5 text-[12px] font-medium text-zinc-600 transition hover:bg-amber-50 hover:text-amber-700 hover:border-amber-200">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                            {{ __('orders::order_composer.actions.revert') }}
                                        </button>
                                    @elseif ($_order->status_id == 30)
                                        <button type="button"
                                            x-on:click="$dispatch('confirm-action', { title: @js(__('orders::order_composer.actions.reopen')), message: @js(__('orders::order_composer.confirm.reopen')), confirmText: @js(__('orders::order_composer.actions.reopen')), tone: 'teal', run: () => $wire.reopenOrder('{{ $_order->order_no }}') })"
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-zinc-200 px-2.5 text-[12px] font-medium text-zinc-600 transition hover:bg-teal-50 hover:text-teal-700 hover:border-teal-200">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                            {{ __('orders::order_composer.actions.reopen') }}
                                        </button>
                                    @endif
                                @endcan
                            @endif

                            @if ($status == 'deleted')
                                @can('edit-orders')
                                    <button wire:click="restoreData('{{ $_order->order_no }}')" title="{{ __('orders::order_list.filters.reset') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-teal-50 hover:text-teal-600">
                                        <x-icons.recover color="text-current" hover="text-current"></x-icons.recover>
                                    </button>
                                @endcan
                            @elseif ($_order->template_render_mode === \App\Services\Orders\Document\OrderIssueService::RENDER_MODE_DOCX && $_order->status_id == 10)
                                @can('add-orders')
                                    <button type="button" wire:click="openSideMenu('order-composer', {{ $_order->id }})"
                                        title="{{ __('orders::order_composer.actions.edit') }}" aria-label="{{ __('orders::order_composer.actions.edit') }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700">
                                        <x-icons.document-icon></x-icons.document-icon>
                                    </button>
                                @endcan
                            @endif

                            @if ($status != 'deleted')
                                @can('delete-orders')
                                    <button wire:click="setDeleteOrder('{{ $_order->order_no }}')" title="Sil"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600">
                                        <x-icons.delete-icon></x-icons.delete-icon>
                                    </button>
                                @endcan
                            @else
                                @can('delete-orders')
                                    <button type="button"
                                        x-on:click="$dispatch('confirm-action', { title: 'Tamamilə sil', message: @js(__('orders::order_list.messages.force_delete_confirm')), confirmText: @js(__('ui::common.actions.delete')), tone: 'rose', run: () => $wire.forceDeleteData('{{ $_order->order_no }}') })"
                                        title="Tamamilə sil"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600">
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </x-table.td>
                </tr>
            @empty
                <x-table.empty :rows="count($this->getTableHeaders())"></x-table.empty>
            @endforelse
        </x-table.tbl>

        <div>
            {{ $this->orders->links() }}
        </div>
    </div>

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

    <x-datepicker :auto=false></x-datepicker>
</div>
