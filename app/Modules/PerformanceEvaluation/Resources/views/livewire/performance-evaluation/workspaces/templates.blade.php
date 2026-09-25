    @if ($activeTab === 'templates')
        @php
            $d = 'performance_evaluation::dashboard';
            $b = 'performance_evaluation::dashboard.builder';
            $templates = $this->builderTemplates;
            $current = $this->builderTemplate;
            $iconButton = 'flex h-10 w-10 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300';
            $num = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
            $canManage = auth()->user()?->can('manage-performance-evaluation');
        @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[288px_minmax(0,1fr)]">
            {{-- ───────────── template list ───────────── --}}
            <aside class="self-start overflow-hidden rounded-2xl border border-hairline bg-white shadow-card lg:sticky lg:top-4">
                <div class="flex items-center justify-between gap-2 border-b border-hairline-subtle px-4 py-3">
                    <div>
                        <p class="text-[13px] font-semibold text-ink">{{ __($b.'.list_title') }}</p>
                        <p class="text-[11.5px] text-ink-faint">{{ __($b.'.list_count', ['count' => $templates->count()]) }}</p>
                    </div>
                    @if ($canManage)
                        <button type="button" wire:click="newTemplate" class="flex h-10 items-center gap-1.5 rounded-lg bg-ink px-2.5 text-[14px] font-semibold text-white hover:bg-ink-hover">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            {{ __($b.'.new') }}
                        </button>
                    @endif
                </div>

                <div class="hrm-scroll max-h-[70vh] overflow-y-auto p-1.5">
                    @forelse ($templates as $template)
                        @php
                            $isCurrent = $current?->id === $template->id;
                            $itemCount = $template->sections->sum(fn ($section) => $section->items->count());
                        @endphp
                        <button type="button" wire:key="builder-template-{{ $template->id }}" wire:click="selectTemplate({{ $template->id }})"
                            class="relative flex w-full items-start gap-3 rounded-xl px-3 py-2.5 text-left transition {{ $isCurrent ? 'bg-[#f4f4f5]' : 'hover:bg-[#fafafa]' }}">
                            @if ($isCurrent)
                                <span class="absolute inset-y-2 left-0 w-[3px] rounded-full bg-ink"></span>
                            @endif
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $template->is_active ? 'bg-emerald-500' : 'bg-zinc-300' }}" title="{{ $template->is_active ? __($d.'.statuses.active') : __($d.'.labels.inactive') }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] {{ $isCurrent ? 'font-semibold text-ink' : 'font-medium text-ink-soft' }}">{{ $template->name }}</span>
                                <span class="mt-0.5 block text-[11.5px] text-ink-faint">{{ __($b.'.list_meta', ['sections' => $template->sections->count(), 'items' => $itemCount]) }}</span>
                            </span>
                        </button>
                    @empty
                        <p class="px-3 py-8 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.recent_templates') }}</p>
                    @endforelse
                </div>
            </aside>

            {{-- ───────────── builder ───────────── --}}
            <section class="min-w-0">
                @if ($current === null)
                    <div class="rounded-2xl border border-dashed border-hairline bg-white px-6 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f4f4f5] text-ink-muted">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                        </div>
                        <p class="mt-4 text-[15px] font-semibold text-ink">{{ __($b.'.empty_title') }}</p>
                        <p class="mx-auto mt-1 max-w-md text-[12.5px] leading-6 text-ink-muted">{{ __($b.'.empty_body') }}</p>
                        @if ($canManage)
                            <button type="button" wire:click="newTemplate" class="mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($b.'.new_template') }}</button>
                        @endif
                    </div>
                @else
                    @php
                        $sectionWeightSum = round((float) $current->sections->sum('weight_percent'), 2);
                        $hasSectionWeights = $sectionWeightSum > 0;
                    @endphp

                    {{-- template head --}}
                    <div class="rounded-2xl border border-hairline bg-white p-5 shadow-card">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ $current->name }}</h2>
                                    @if ($current->code)
                                        <span class="hrm-num rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted">{{ $current->code }}</span>
                                    @endif
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $current->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $current->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                        {{ $current->is_active ? __($d.'.statuses.active') : __($d.'.labels.inactive') }}
                                    </span>
                                </div>
                                @if (filled($current->description))
                                    <p class="mt-1.5 max-w-2xl text-[13px] leading-6 text-ink-muted">{{ $current->description }}</p>
                                @endif
                            </div>
                            @if ($canManage)
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" wire:click="openTemplateEditor({{ $current->id }})" class="{{ $iconButton }}" title="{{ __($d.'.actions.edit') }}" aria-label="{{ __($d.'.actions.edit') }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirmDeleteTemplate({{ $current->id }})" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($d.'.actions.delete') }}" aria-label="{{ __($d.'.actions.delete') }}">
                                        <x-icons.delete-icon size="h-4 w-4" />
                                    </button>
                                    <button type="button" wire:click="newSection({{ $current->id }})" class="ml-1 flex h-10 items-center gap-1.5 rounded-xl bg-ink px-3.5 text-[14px] font-semibold text-white hover:bg-ink-hover">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                                        {{ __($b.'.add_section') }}
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- how the pieces fit --}}
                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach (['template', 'section', 'item'] as $step)
                                <div class="flex items-start gap-2.5 rounded-xl bg-[#fafafa] px-3 py-2.5">
                                    <span class="hrm-num flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-ink ring-1 ring-hairline">{{ $loop->iteration }}</span>
                                    <span class="text-[12px] leading-5 text-ink-muted"><span class="font-semibold text-ink">{{ __($b.'.explain.'.$step.'.title') }}</span> — {{ __($b.'.explain.'.$step.'.body') }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if ($current->sections->isNotEmpty())
                            <div class="mt-4">
                                <div class="flex items-center justify-between text-[11.5px]">
                                    <span class="text-ink-muted">{{ __($b.'.section_weights') }}</span>
                                    @if ($hasSectionWeights)
                                        <span class="hrm-num font-semibold {{ abs($sectionWeightSum - 100) < 0.01 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $num($sectionWeightSum) }}%</span>
                                    @else
                                        <span class="text-ink-faint">{{ __($b.'.equal_weights') }}</span>
                                    @endif
                                </div>
                                @if ($hasSectionWeights)
                                    <div class="mt-1.5 flex h-2 gap-0.5 overflow-hidden rounded-full bg-[#f4f4f5]">
                                        @foreach ($current->sections as $section)
                                            <div class="h-full {{ ['bg-ink', 'bg-zinc-500', 'bg-zinc-400', 'bg-zinc-300'][$loop->index % 4] }}" style="width: {{ min(100, (float) $section->weight_percent) }}%" title="{{ $section->name }} · {{ $num($section->weight_percent) }}%"></div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- sections --}}
                    <div class="mt-3 flex flex-col gap-3">
                        @forelse ($current->sections as $section)
                            <div wire:key="builder-section-{{ $section->id }}" class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
                                <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="hrm-num flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-[12px] font-semibold text-ink ring-1 ring-hairline">{{ $loop->iteration }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-[14px] font-semibold text-ink">{{ $section->name }}</p>
                                            <p class="text-[11.5px] text-ink-faint">{{ __($b.'.items_count', ['count' => $section->items->count()]) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1">
                                        @if ((float) $section->weight_percent > 0)
                                            <span class="hrm-num mr-1 rounded-md bg-white px-2 py-0.5 text-[11.5px] font-semibold text-ink ring-1 ring-hairline">{{ $num($section->weight_percent) }}%</span>
                                        @endif
                                        @if ($canManage)
                                            <button type="button" wire:click="openSectionEditor({{ $section->id }})" class="{{ $iconButton }}" title="{{ __($d.'.actions.edit') }}" aria-label="{{ __($d.'.actions.edit') }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            </button>
                                            <button type="button" wire:click="confirmDeleteSection({{ $section->id }})" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($d.'.actions.delete') }}" aria-label="{{ __($d.'.actions.delete') }}">
                                                <x-icons.delete-icon size="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="divide-y divide-hairline-subtle">
                                    @foreach ($section->items as $item)
                                        <div wire:key="builder-item-{{ $item->id }}" class="group flex flex-col gap-2 px-5 py-3 transition-colors hover:bg-[#fafafa] sm:flex-row sm:items-center sm:gap-4">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[13px] font-medium text-ink">{{ $item->name }}</p>
                                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11.5px]">
                                                    @if ($item->competency)
                                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-ink-muted">
                                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
                                                            {{ $item->competency->name }}
                                                        </span>
                                                    @endif
                                                    <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-amber-700" title="{{ __($b.'.threshold_hint') }}">{{ __($b.'.threshold', ['value' => $num($item->low_score_threshold)]) }}</span>
                                                    @if ($item->requires_comment)
                                                        <span class="rounded-md bg-sky-50 px-1.5 py-0.5 text-sky-700">{{ __($b.'.comment_required') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                @if ((float) $item->weight_percent > 0)
                                                    <div class="flex items-center gap-2">
                                                        <div class="h-1 w-16 overflow-hidden rounded-full bg-[#f4f4f5]">
                                                            <div class="h-full rounded-full bg-zinc-400" style="width: {{ min(100, (float) $item->weight_percent) }}%"></div>
                                                        </div>
                                                        <span class="hrm-num w-10 text-right text-[12px] text-ink">{{ $num($item->weight_percent) }}%</span>
                                                    </div>
                                                @endif
                                                @if ($canManage)
                                                    <div class="flex items-center gap-0.5 sm:opacity-0 sm:transition-opacity sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                                                        <button type="button" wire:click="openItemEditor({{ $item->id }})" class="{{ $iconButton }}" title="{{ __($d.'.actions.edit') }}" aria-label="{{ __($d.'.actions.edit') }}">
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                        </button>
                                                        <button type="button" wire:click="confirmDeleteItem({{ $item->id }})" class="{{ $iconButton }} hover:!bg-rose-50" title="{{ __($d.'.actions.delete') }}" aria-label="{{ __($d.'.actions.delete') }}">
                                                            <x-icons.delete-icon size="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($canManage)
                                    <div class="px-5 py-2.5 {{ $section->items->isNotEmpty() ? 'border-t border-hairline-subtle' : '' }}">
                                        <button type="button" wire:click="newItem({{ $section->id }})" class="inline-flex h-10 items-center gap-1.5 rounded-lg px-2 text-[14px] font-medium text-ink-muted hover:bg-[#f4f4f5] hover:text-ink">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                                            {{ __($b.'.add_item') }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-hairline bg-white px-6 py-12 text-center">
                                <p class="text-[13.5px] font-medium text-ink">{{ __($b.'.no_sections') }}</p>
                                <p class="mt-1 text-[12.5px] text-ink-faint">{{ __($b.'.no_sections_hint') }}</p>
                                @if ($canManage)
                                    <button type="button" wire:click="newSection({{ $current->id }})" class="mt-4 inline-flex h-10 items-center gap-1.5 rounded-xl bg-ink px-3.5 text-[14px] font-semibold text-white hover:bg-ink-hover">{{ __($b.'.add_section') }}</button>
                                @endif
                            </div>
                        @endforelse
                    </div>
                @endif
            </section>
        </div>

        {{-- ───────────── editors ───────────── --}}
        @if ($canManage)
            <x-side-modal size="large">
                @if ($showSideMenu === 'form-template')
                    <div class="flex h-full flex-col">
                        <p class="hrm-eyebrow">{{ __($b.'.explain.template.title') }}</p>
                        <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ $editingTemplateId ? __($b.'.edit_template') : __($b.'.new_template') }}</h2>
                        <p class="mt-1.5 text-[12.5px] leading-6 text-ink-muted">{{ __($d.'.labels.template_setup_hint') }}</p>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <x-label for="template-name" value="{{ __($d.'.fields.template_name') }}" />
                                <x-livewire-input mode="gray" id="template-name" name="templateForm.name" wire:model="templateForm.name" />
                                @error('templateForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="template-code" value="{{ __($d.'.fields.template_code') }}" />
                                <x-livewire-input mode="gray" id="template-code" name="templateForm.code" wire:model="templateForm.code" />
                                @error('templateForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="sm:col-span-3">
                                <x-label for="template-description" value="{{ __($d.'.fields.description') }}" />
                                <textarea id="template-description" wire:model="templateForm.description" rows="3" class="w-full rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none"></textarea>
                            </div>
                            <label class="flex items-center gap-2.5 text-[13px] text-ink-soft sm:col-span-3">
                                <input type="checkbox" wire:model="templateForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                                {{ __($d.'.fields.is_active') }}
                            </label>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                            <button type="button" wire:click="closeBuilderPanel" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($d.'.actions.cancel_edit') }}</button>
                            <button type="button" wire:click="saveBuilderTemplate" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_template') }}</button>
                        </div>
                    </div>
                @endif

                @if ($showSideMenu === 'form-section')
                    <div class="flex h-full flex-col">
                        <p class="hrm-eyebrow">{{ $current?->name }}</p>
                        <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ $editingSectionId ? __($b.'.edit_section') : __($b.'.add_section') }}</h2>
                        <p class="mt-1.5 text-[12.5px] leading-6 text-ink-muted">{{ __($d.'.labels.section_setup_hint') }}</p>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-label for="section-name" value="{{ __($d.'.fields.section_name') }}" />
                                <x-livewire-input mode="gray" id="section-name" name="sectionForm.name" wire:model="sectionForm.name" />
                                @error('sectionForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                                @error('sectionForm.performance_form_template_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="section-weight" value="{{ __($d.'.fields.weight_percent') }}" />
                                <x-livewire-input mode="gray" id="section-weight" type="number" step="0.01" name="sectionForm.weight_percent" wire:model="sectionForm.weight_percent" />
                                @error('sectionForm.weight_percent') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="section-sort" value="{{ __($d.'.fields.sort_order') }}" />
                                <x-livewire-input mode="gray" id="section-sort" type="number" name="sectionForm.sort_order" wire:model="sectionForm.sort_order" />
                            </div>
                            <p class="rounded-xl bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted sm:col-span-2">{{ __($b.'.section_weight_hint') }}</p>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                            <button type="button" wire:click="closeBuilderPanel" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($d.'.actions.cancel_edit') }}</button>
                            <button type="button" wire:click="saveBuilderSection" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_section') }}</button>
                        </div>
                    </div>
                @endif

                @if ($showSideMenu === 'form-item')
                    <div class="flex h-full flex-col">
                        <p class="hrm-eyebrow">{{ $current?->sections->firstWhere('id', $itemForm['performance_form_template_section_id'] ?? null)?->name }}</p>
                        <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ $editingItemId ? __($b.'.edit_item') : __($b.'.add_item') }}</h2>
                        <p class="mt-1.5 text-[12.5px] leading-6 text-ink-muted">{{ __($d.'.labels.item_setup_hint') }}</p>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-label for="item-name" value="{{ __($d.'.fields.item_name') }}" />
                                <x-livewire-input mode="gray" id="item-name" name="itemForm.name" wire:model="itemForm.name" />
                                @error('itemForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                                @error('itemForm.performance_form_template_section_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.select-dropdown :label="__($d.'.fields.competency')" placeholder="---" mode="gray" class="w-full" instance="perf-builder-item-competency"
                                    wire:model.live="itemForm.training_competency_id" :model="$this->competencyOptions()" search-model="searchCompetency"></x-ui.select-dropdown>
                                @error('itemForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="item-weight" value="{{ __($d.'.fields.weight_percent') }}" />
                                <x-livewire-input mode="gray" id="item-weight" type="number" step="0.01" name="itemForm.weight_percent" wire:model="itemForm.weight_percent" />
                                @error('itemForm.weight_percent') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="item-threshold" value="{{ __($d.'.fields.low_score_threshold') }}" />
                                <x-livewire-input mode="gray" id="item-threshold" type="number" step="0.01" name="itemForm.low_score_threshold" wire:model="itemForm.low_score_threshold" />
                                @error('itemForm.low_score_threshold') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <label class="flex items-center gap-2.5 text-[13px] text-ink-soft sm:col-span-2">
                                <input type="checkbox" wire:model="itemForm.requires_comment" class="h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400">
                                {{ __($d.'.fields.requires_comment') }}
                            </label>
                            <p class="rounded-xl bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted sm:col-span-2">{{ __($b.'.threshold_hint') }}</p>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                            <button type="button" wire:click="closeBuilderPanel" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($d.'.actions.cancel_edit') }}</button>
                            <button type="button" wire:click="saveBuilderItem" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_item') }}</button>
                        </div>
                    </div>
                @endif
            </x-side-modal>
        @endif
    @endif
