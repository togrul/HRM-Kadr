<div class="contents">
    @if ($tabelNo)
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.bank.list') }}</h2>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->bankAccounts as $account)
                    <div wire:key="compensation-bank-{{ $account->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="hrm-num truncate text-[13px] font-medium text-ink">{{ $account->iban }}</p>
                            <p class="truncate text-[11.5px] text-ink-faint">{{ $account->bank_name }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            @if ($account->is_primary)
                                <x-small-badge mode="green" dot>{{ __('compensation::dashboard.fields.is_primary') }}</x-small-badge>
                            @endif
                            @if ($canManage)
                                <button type="button" wire:click="editBank({{ $account->id }})" title="{{ __('compensation::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                <button type="button" x-on:click="{{ $confirmDelete('deleteBank('.$account->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.bank.empty')" />
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel !== '')
        <x-ui.side-panel
            title-id="compensation-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('compensation::dashboard.actions.close')"
            width="3xl"
        >
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => __('compensation::dashboard.bank.title')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.iban')" :error="$errors->first('bankForm.iban')">
                        <x-ui.input class="uppercase" wire:model="bankForm.iban" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.bank_name')">
                        <x-ui.input wire:model="bankForm.bank_name" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.account_no')">
                        <x-ui.input wire:model="bankForm.account_no" />
                    </x-ui.input-shell>
                    <div class="flex flex-wrap gap-4 pt-1 sm:col-span-2">
                        @foreach (['is_primary', 'is_active'] as $flag)
                            <label class="inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted">
                                <input type="checkbox" wire:model="bankForm.{{ $flag }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                {{ __('compensation::dashboard.fields.'.$flag) }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => 'saveBank'])
        </x-ui.side-panel>
    @endif
</div>
