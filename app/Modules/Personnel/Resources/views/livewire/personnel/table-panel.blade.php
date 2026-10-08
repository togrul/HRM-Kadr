@php
    $personnels = $this->personnels;
    $status = $this->status;

    // One presence state per row drives the chip colour and the avatar tint; the
    // avatar stays grey for the common at-work state so tinted avatars flag exceptions.
    $stateOf = function ($personnel): array {
        // Silinmiş qeyd işdə/işdən çıxmış statusu ilə göstərilməməlidir.
        if ($personnel->trashed()) {
            return ['key' => 'deleted', 'tone' => 'rose', 'label' => __('personnel::common.states.deleted')];
        }

        if (filled($personnel->leave_work_date)) {
            return ['key' => 'resigned', 'tone' => 'rose', 'label' => __('personnel::common.labels.resigned')];
        }

        if ($personnel->is_pending) {
            return ['key' => 'pending', 'tone' => 'amber', 'label' => __('personnel::common.states.waiting_for_approval')];
        }

        if ($personnel->active_vacation) {
            return ['key' => 'vacation', 'tone' => 'violet', 'label' => __('personnel::common.states.in_vacation')];
        }

        if ($personnel->active_business_trip) {
            return ['key' => 'trip', 'tone' => 'blue', 'label' => __('personnel::common.states.in_business_trip')];
        }

        return ['key' => 'at_work', 'tone' => 'green', 'label' => __('personnel::common.states.at_work')];
    };
@endphp

<div class="contents">
    <x-table.tbl sticky :headers="$this->getTableHeaders()">
        @forelse ($personnels as $personnel)
            @php
                $rowActions = $this->rowActions($personnel);
                $state = $stateOf($personnel);
            @endphp

            <tr
                wire:key="personnel-row-{{ $personnel->id }}-{{ $status ?? 'all' }}"
                @class([
                    'group/row transition',
                    'bg-[#fffbf5]' => $state['key'] === 'pending',
                    'bg-[#fff7f8]' => in_array($state['key'], ['resigned', 'deleted'], true),
                    'hover:bg-[#fafafa]' => ! in_array($state['key'], ['pending', 'resigned', 'deleted'], true),
                ])
            >
                <x-table.td>
                    <a href="{{ route('personnel.show', $personnel->id) }}" wire:navigate class="flex items-center gap-3">
                        <x-avatar :name="$personnel->fullname" :tone="$state['key'] === 'at_work' ? 'neutral' : $state['tone']" />
                        <div class="min-w-0 max-w-[240px] leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink group-hover/row:underline">{{ $personnel->fullname }}</p>
                            <p class="hrm-num mt-0.5 text-[11.5px] text-ink-faint">#{{ $personnel->tabel_no }}</p>
                        </div>
                    </a>
                </x-table.td>

                <x-table.td standart-width wire:click="handleRowAction('quick-view', { type: 'quick-view', value: '{{ $personnel->tabel_no }}' })" class="cursor-pointer">
                    <div class="max-w-[280px] leading-tight">
                        <p class="truncate text-[13px] text-ink-soft">{{ $personnel->position_label }}</p>
                        {{-- the column shows the current unit only; the full chain stays on hover --}}
                        <p class="mt-0.5 truncate text-[11.5px] text-ink-faint" title="{{ $personnel->structure_path }}">{{ $personnel->structure_name }}</p>
                    </div>
                </x-table.td>

                <x-table.td wire:click="handleRowAction('quick-view', { type: 'quick-view', value: '{{ $personnel->tabel_no }}' })" class="cursor-pointer">
                    <x-small-badge :mode="$state['tone']" dot>
                        {{ $state['label'] }}
                    </x-small-badge>

                    @if ($status === 'deleted')
                        <p class="mt-1 text-[11px] text-ink-faint">
                            {{ $personnel->deleted_at_fmt }}{{ $personnel->deleted_by_name ? ' · '.$personnel->deleted_by_name : '' }}
                        </p>
                    @endif
                </x-table.td>

                <x-table.td wire:click="handleRowAction('quick-view', { type: 'quick-view', value: '{{ $personnel->tabel_no }}' })" class="cursor-pointer">
                    <span class="hrm-num text-[13px] text-ink-soft">{{ $personnel->join_work_date_fmt }}</span>
                    @if (filled($personnel->leave_work_date))
                        <span class="hrm-num mt-0.5 block text-[11.5px] text-[#be123c]">{{ $personnel->leave_work_date_fmt }}</span>
                    @endif
                </x-table.td>

                <x-personnel.row-actions :actions="$rowActions" :force-up="$loop->last" />
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" :filtered="$search !== '' || $filters !== [] || $selectedPosition !== null" :resettable="false" />
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$personnels" :unit="__('ui::common.labels.results')" />
</div>
