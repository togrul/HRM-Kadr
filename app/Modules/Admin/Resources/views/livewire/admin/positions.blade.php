<div class="flex flex-col"
     x-data
     x-init="
        const root = $el;
        const paintPaginator = () => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (paginator) {
                paginator.classList.add('bg-blue-50', 'text-blue-600');
            }
        };
        paintPaginator();
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== $wire.__instance.id) return;
                succeed(() => queueMicrotask(paintPaginator));
            });
        }
    "
>
    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('admin::references.buttons.add_position') }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div wire:transition class="relative my-3 flex rounded-2xl border border-zinc-200 bg-white px-4 py-4 shadow-sm">
            <x-action-button class="absolute right-2 top-2 h-9 w-9 hover:bg-zinc-100" wire:click="closeCrud()" :title="__('admin::references.actions.close')">
                <x-icons.close-icon></x-icons.close-icon>
            </x-action-button>
            <div class="mt-6 grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-6">
                <div class="flex flex-col">
                    <x-label for="form.id">{{ __('admin::references.fields.id') }}</x-label>
                    <x-livewire-input mode="default" type="number" name="form.id" wire:model="form.id"></x-livewire-input>
                    @error('form.id')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-ui.select-dropdown
                        :label="__('admin::references.fields.rank_category')"
                        placeholder="---"
                        mode="default"
                        class="w-full"
                        wire:model.live="form.rank_category_id"
                        :model="$this->rankCategoryOptions()"
                    search-model="searchRankCategory"
                    >
                    </x-ui.select-dropdown>
                    @error('form.rank_category_id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.name">{{ __('admin::references.fields.name') }}</x-label>
                    <x-livewire-input mode="default" name="form.name" wire:model="form.name"></x-livewire-input>
                    @error('form.name')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.approval_rank">{{ __('admin::references.fields.approval_rank') }}</x-label>
                    <x-livewire-input mode="default" type="number" name="form.approval_rank" wire:model="form.approval_rank"></x-livewire-input>
                    @error('form.approval_rank')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col justify-end">
                    <label class="inline-flex items-center gap-2 pb-2">
                        <input type="checkbox" wire:model.live="form.is_approval_target" class="rounded border-gray-300 text-slate-700 focus:ring-slate-300">
                        <span class="text-sm text-gray-700">{{ __('admin::references.fields.is_approval_target') }}</span>
                    </label>
                    @error('form.is_approval_target')
                        <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex items-end">
                    <x-modal-button mode="black">{{ __('admin::references.actions.save') }}</x-modal-button>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('admin::references.fields.id'),__('admin::references.fields.category'),__('admin::references.fields.name'),__('admin::references.fields.approval_rank'),__('admin::references.fields.is_approval_target'),__('admin::references.table.action')]">
                        @forelse ($positions as $position)
                            <tr>
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $position->id }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                      <span @class([
                                            'text-sm font-medium text-blue-500',
                                            'bg-slate-100 rounded-sm px-3 py-1' => $position->rankCategory
                                      ])>
                                          {{ $position->rankCategory?->name }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $position->name }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $position->approval_rank ?? 0 }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                      <span @class([
                                            'text-sm font-medium rounded-sm px-3 py-1',
                                            'text-emerald-600 bg-emerald-50' => $position->is_approval_target,
                                            'text-zinc-500 bg-slate-100' => ! $position->is_approval_target,
                                      ])>
                                          {{ $position->is_approval_target ? __('admin::references.fields.is_active') : '—' }}
                                      </span>
                                </x-table.td>
                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <x-action-button
                                            wire:click.prevent="openCrud({{ $position->id }})"
                                            class="h-9 w-9 hover:bg-zinc-100"
                                            :title="__('admin::references.actions.edit')"
                                        >
                                            <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                                        </x-action-button>
                                        <x-action-button
                                            wire:click.prevent = "deleteModel({{ $position->id }})"
                                            class="h-9 w-9 hover:bg-red-100"
                                            :title="__('admin::references.actions.delete')"
                                        >
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </x-action-button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </div>
</div>
@include('includes.sweetalert-push')
