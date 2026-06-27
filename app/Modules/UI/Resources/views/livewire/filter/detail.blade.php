<div
    x-data="{ open: false }"
    x-init="
        $wire.on('filterSelected', () => { open = false });
        $wire.on('openFilterWasSet', () => {
            open = true;
            $nextTick(() => $refs.applyFiltersButton?.focus());
        });
    "
    x-show="open"
    x-on:open-filter-modal.window="open = true; $nextTick(() => $refs.applyFiltersButton?.focus())"
    x-on:keydown.escape.window="open = false"
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-50"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]" @click="open = false"></div>

    <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-6xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <header class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('ui::filters.titles.advanced_filters') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('ui::filters.descriptions.refine_results') }}</p>
                </div>

                <button type="button" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" @click="open = false">
                  <x-icons.default.close-icon size="w-6 h-6" color="text-slate-500" hover="text-slate-900"></x-icons.default.close-icon>
                </button>
            </header>

            <div wire:key="filter-detail-open-{{ $openSequence }}" class="max-h-[72vh] space-y-6 overflow-y-auto px-6 py-6">
                <section class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M6 12h12M10 17h4" />
                        </svg>
                        {{ __('ui::filters.sections.organizational') }}
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-6 xl:grid-cols-12">
                        <div class="lg:col-span-3 xl:col-span-4">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.structure')"
                                :options="$this->structureOptions"
                                searchModel="searchStructure"
                                load-on-open="structure"
                                load-on-focus="structure"
                                wire:model.live="filter.structure_id"
                            />
                        </div>
                        <div class="lg:col-span-3 xl:col-span-4">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.position')"
                                :options="$this->positions"
                                searchModel="searchPosition"
                                load-on-open="position"
                                load-on-focus="position"
                                wire:model.defer="filter.position_id"
                            />
                        </div>
                        <div class="lg:col-span-2 xl:col-span-4">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.nationality')"
                                :options="$this->nationalityOptions"
                                searchModel="searchNationality"
                                load-on-open="nationality"
                                load-on-focus="nationality"
                                wire:model.defer="filter.nationality_id"
                            />
                        </div>
                        <div class="lg:col-span-2 xl:col-span-6">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.born_country')"
                                :options="$this->bornCountryOptions"
                                searchModel="searchPreviousNationality"
                                load-on-open="bornCountry"
                                load-on-focus="bornCountry"
                                wire:model.defer="filter.born_country_id"
                            />
                        </div>
                        <div class="lg:col-span-2 xl:col-span-6">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.city')"
                                :options="$this->cities"
                                searchModel="searchCity"
                                load-on-open="city"
                                load-on-focus="city"
                                wire:model.defer="filter.born_city_id"
                            />
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19a6 6 0 10-12 0m12 0h3m-3 0a6 6 0 0112 0m-6-10a4 4 0 110-8 4 4 0 010 8zM9 11a4 4 0 110-8 4 4 0 010 8z" />
                        </svg>
                        {{ __('ui::filters.sections.status_personal') }}
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-8 xl:grid-cols-12">
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.surname">{{ __('personnel::common.labels.surname') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.surname" wire:model.defer="filter.surname" />
                        </div>
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.name">{{ __('personnel::common.labels.name') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.name" wire:model.defer="filter.name" />
                        </div>
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.patronymic">{{ __('personnel::common.labels.patronymic') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.patronymic" wire:model.defer="filter.patronymic" />
                        </div>
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.tabel_no">{{ __('personnel::common.labels.tabel_hash') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.tabel_no" wire:model.defer="filter.tabel_no" />
                        </div>
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.pin">{{ __('personnel::common.labels.pin') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.pin" wire:model.defer="filter.pin" />
                        </div>
                        <div class="flex flex-col gap-1 lg:col-span-4 xl:col-span-2">
                            <x-label for="filter.is_married">{{ __('personnel::common.labels.family_status') }}</x-label>
                            <div class="flex flex-row">
                                <x-ui.radio model="filter.is_married" value="0" :label="__('personnel::common.labels.single')" />
                                <x-ui.radio model="filter.is_married" value="1" :label="__('personnel::common.labels.married')" />
                            </div>
                        </div>
                        <div class="flex flex-col lg:col-span-4 xl:col-span-4">
                            <x-label for="filter.gender">{{ __('personnel::common.labels.gender') }}</x-label>
                            <div class="flex flex-col lg:flex-row lg:flex-wrap lg:gap-3">
                                @foreach(\App\Enums\GenderEnum::genderOptions() as $value => $label)
                                    <x-ui.radio model="filter.gender" :$value :$label />
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-col lg:col-span-4 xl:col-span-5">
                            <x-label for="filter.birthdate">{{ __('personnel::common.labels.birthdate') }}</x-label>
                            <div class="flex items-center space-x-1">
                                <x-pikaday-input mode="gray" name="filter.birthdate.min" format="Y-MM-DD" wire:model.live="filter.birthdate.min">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.birthdate.min', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                                <span>-</span>
                                <x-pikaday-input mode="gray" name="filter.birthdate.max" format="Y-MM-DD" wire:model.live="filter.birthdate.max">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.birthdate.max', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                            </div>
                        </div>
                        <div class="flex flex-col lg:col-span-4 xl:col-span-3">
                            <x-label for="filter.age">{{ __('candidates::common.labels.age') }}</x-label>
                            <div class="flex items-center space-x-1">
                                <x-livewire-input mode="gray" name="filter.age.min" wire:model.defer="filter.age.min" />
                                <span>-</span>
                                <x-livewire-input mode="gray" name="filter.age.max" wire:model.defer="filter.age.max" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M9 8h6M4 5h16v14H4V5z" />
                        </svg>
                        {{ __('ui::filters.sections.employment') }}
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-8 xl:grid-cols-12">
                        <div class="flex flex-col lg:col-span-5 xl:col-span-4">
                            <x-label for="filter.rank">{{ __('personnel::common.labels.rank') }} {{ __('personnel::common.labels.date') }}</x-label>
                            <div class="flex items-center space-x-1">
                                <x-pikaday-input mode="gray" name="filter.rank.min" format="Y-MM-DD" wire:model.live="filter.rank.min">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.rank.min', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                                <span>-</span>
                                <x-pikaday-input mode="gray" name="filter.rank.max" format="Y-MM-DD" wire:model.live="filter.rank.max">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.rank.max', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                            </div>
                        </div>
                        <div class="lg:col-span-3 xl:col-span-2">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.ranks')"
                                :options="$this->rankOptions"
                                searchModel="searchRank"
                                load-on-open="rank"
                                load-on-focus="rank"
                                wire:model.defer="filter.rank_id"
                            />
                        </div>
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.rank_name">{{ __('personnel::common.labels.rank') }} {{ __('personnel::common.labels.name') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.rank_name" wire:model.defer="filter.rank_name" />
                        </div>
                        <div class="lg:col-span-2 xl:col-span-2">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.education_degree')"
                                :options="$this->educationDegreeOptions"
                                searchModel="searchEducationDegree"
                                load-on-open="educationDegree"
                                load-on-focus="educationDegree"
                                wire:model.defer="filter.education_degree_id"
                            />
                        </div>
                        <div class="flex flex-col lg:col-span-2 xl:col-span-2">
                            <x-label for="filter.specialty">{{ __('personnel::common.labels.specialty') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.specialty" wire:model.defer="filter.specialty" />
                        </div>
                        <div class="lg:col-span-4 xl:col-span-4">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.education_place')"
                                :options="$this->institutionOptions"
                                searchModel="searchInstitution"
                                load-on-open="institution"
                                load-on-focus="institution"
                                wire:model.defer="filter.educational_institution_id"
                            />
                        </div>
                        <div class="lg:col-span-4 xl:col-span-3">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.awards')"
                                :options="$this->awardOptions"
                                searchModel="searchAward"
                                load-on-open="award"
                                load-on-focus="award"
                                wire:model.defer="filter.award_id"
                            />
                        </div>
                        <div class="lg:col-span-2 xl:col-span-2">
                            <x-ui.filter-select
                                :label="__('personnel::common.labels.punishments')"
                                :options="$this->punishmentOptions"
                                searchModel="searchPunishment"
                                load-on-open="punishment"
                                load-on-focus="punishment"
                                wire:model.defer="filter.punishment_id"
                            />
                        </div>
                        <div class="flex flex-col lg:col-span-6 xl:col-span-3">
                            <x-label for="filter.punishment_reason">{{ __('personnel::common.labels.punishment') }} {{ __('personnel::common.labels.reason') }}</x-label>
                            <x-livewire-input mode="gray" name="filter.punishment_reason" wire:model.defer="filter.punishment_reason" />
                        </div>
                        <div class="flex flex-col lg:col-span-4 xl:col-span-3">
                            <x-label for="filter.join_work_date">{{ __('personnel::common.labels.join_work_date') }}</x-label>
                            <div class="flex items-center space-x-1">
                                <x-pikaday-input mode="gray" name="filter.join_work_date.min" format="Y-MM-DD" wire:model.live="filter.join_work_date.min">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.join_work_date.min', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                                <span>-</span>
                                <x-pikaday-input mode="gray" name="filter.join_work_date.max" format="Y-MM-DD" wire:model.live="filter.join_work_date.max">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.join_work_date.max', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                            </div>
                        </div>
                        <div class="flex flex-col lg:col-span-4 xl:col-span-3">
                            <x-label for="filter.leave_work_date">{{ __('personnel::common.labels.leave_work_date') }}</x-label>
                            <div class="flex items-center space-x-1">
                                <x-pikaday-input mode="gray" name="filter.leave_work_date.min" format="Y-MM-DD" wire:model.live="filter.leave_work_date.min">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.leave_work_date.min', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                                <span>-</span>
                                <x-pikaday-input mode="gray" name="filter.leave_work_date.max" format="Y-MM-DD" wire:model.live="filter.leave_work_date.max">
                                    <x-slot name="script">
                                      $el.onchange = function () {
                                      @this.set('filter.leave_work_date.max', $el.value);
                                      }
                                    </x-slot>
                                </x-pikaday-input>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <footer class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" wire:click="clearAllFilters" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                    {{ __('ui::filters.actions.clear_all') }}
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-slate-200 bg-white px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">
                        {{ __('ui::filters.actions.cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="search"
                        wire:loading.attr="disabled"
                        wire:target="search"
                        x-ref="applyFiltersButton"
                        class="rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ __('ui::filters.actions.apply_filters') }}
                    </button>
                </div>
            </footer>
        </div>
    </div>
</div>
