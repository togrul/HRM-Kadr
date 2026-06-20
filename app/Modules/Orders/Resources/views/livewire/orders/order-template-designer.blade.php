<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- ============ Sticky toolbar: name + code + save ============ --}}
    <div class="sticky top-4 z-20 mb-5 rounded-2xl border border-zinc-200/70 bg-white/70 px-3 py-2.5 shadow-[0_1px_3px_rgba(0,0,0,0.04)] backdrop-blur-xl">
        <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center">
            <div class="flex items-center gap-2.5">
                @if (! $isNew)
                    <a href="{{ route('orders.designer') }}" wire:navigate title="{{ __('orders::order_composer.designer.existing_title') }}"
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 transition hover:border-zinc-400 hover:text-zinc-900">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                @endif
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-zinc-800 to-black text-white shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="leading-tight">
                    <h1 class="text-sm font-semibold tracking-tight text-zinc-900">{{ __('orders::order_composer.designer.title') }}</h1>
                    <p class="hidden text-[11px] text-zinc-400 sm:block">{{ __('orders::order_composer.designer.subtitle') }}</p>
                </div>
            </div>

            <div class="flex flex-1 flex-wrap items-center gap-2 lg:justify-end">
                <input type="text" wire:model="label" placeholder="{{ __('orders::order_composer.designer.name') }}"
                    class="h-8 flex-1 min-w-[180px] rounded-lg border border-zinc-200 bg-zinc-50/80 px-2.5 text-[13px] text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-400 focus:bg-white focus:ring-0">
                <input type="text" wire:model="code" @disabled(! $isNew) placeholder="{{ __('orders::order_composer.designer.code') }}"
                    class="h-8 w-32 rounded-lg border border-zinc-200 bg-zinc-50/80 px-2.5 text-[13px] text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-400 focus:bg-white focus:ring-0 disabled:opacity-50">
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 text-[13px] font-medium text-white shadow-sm transition hover:bg-zinc-700 active:scale-[0.98] disabled:opacity-50">
                    <svg wire:loading.remove wire:target="save" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <svg wire:loading wire:target="save" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                    {{ __('orders::order_composer.designer.save') }}
                </button>
            </div>
        </div>
        @error('code') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
        @error('label') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- ============ Existing saved templates (landing only) ============ --}}
    @if ($isNew)
        <div class="mb-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-1 flex items-center gap-2">
                <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.existing_title') }}</h3>
                @if (count($this->templates))
                    <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500">{{ count($this->templates) }}</span>
                @endif
            </div>
            <p class="mb-3 text-[12px] text-zinc-400">{{ __('orders::order_composer.designer.existing_hint') }}</p>

            @if (count($this->templates) === 0)
                <div class="rounded-xl border border-dashed border-zinc-200 py-8 text-center text-[13px] text-zinc-400">
                    {{ __('orders::order_composer.designer.existing_empty') }}
                </div>
            @else
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($this->templates as $tplCode => $tplLabel)
                        <a href="{{ route('orders.designer', $tplCode) }}" wire:navigate
                            class="group flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50/40 px-3.5 py-3 transition hover:border-zinc-900 hover:bg-white">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-900/90 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[13px] font-medium text-zinc-900">{{ $tplLabel }}</div>
                                <div class="truncate text-[11px] text-zinc-400">{{ $tplCode }}</div>
                            </div>
                            <span class="shrink-0 text-[12px] font-medium text-zinc-400 transition group-hover:text-zinc-900">{{ __('orders::order_composer.designer.open_edit') }} →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ============ Variable dictionary (helper while preparing Word) ============ --}}
    <div class="mb-5 rounded-2xl border border-zinc-200 bg-white shadow-sm"
        x-data="{ open: true, copied: '' }">
        <button type="button" x-on:click="open = !open"
            class="flex w-full items-center gap-2 px-5 py-3.5 text-left">
            <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <div class="leading-tight">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.dict_title') }}</h3>
                <p class="text-[11px] text-zinc-400">{{ __('orders::order_composer.designer.dict_subtitle') }}</p>
            </div>
            <svg class="ml-auto h-4 w-4 text-zinc-400 transition" x-bind:class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div x-show="open" x-collapse class="border-t border-zinc-100 px-5 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($this->variableGroups as $group => $vars)
                    <div>
                        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">{{ $group }}</div>
                        <div class="space-y-1">
                            @foreach ($vars as $v)
                                <button type="button"
                                    x-on:click="navigator.clipboard.writeText('[{{ $v['label'] }}]'); copied = @js($v['label']); setTimeout(() => copied = '', 1500)"
                                    class="group flex w-full items-center gap-2 rounded-lg border border-transparent px-2 py-1.5 text-left transition hover:border-zinc-200 hover:bg-zinc-50">
                                    <code class="shrink-0 rounded bg-zinc-100 px-1.5 py-0.5 text-[12px] font-medium text-zinc-700">[{{ $v['label'] }}]</code>
                                    <span class="truncate text-[11px] text-zinc-400">{{ $v['sample'] }}</span>
                                    <span class="ml-auto shrink-0 text-[10px] font-medium"
                                        x-text="copied === @js($v['label']) ? '{{ __('orders::order_composer.designer.copied') }}' : ''"
                                        :class="copied === @js($v['label']) ? 'text-emerald-600' : ''"></span>
                                    <svg x-show="copied !== @js($v['label'])" class="ml-auto h-3.5 w-3.5 shrink-0 text-zinc-300 opacity-0 transition group-hover:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 rounded-lg bg-amber-50/70 px-3 py-2 text-[11px] leading-relaxed text-amber-800">
                {{ __('orders::order_composer.designer.dict_hint') }}
            </p>
        </div>
    </div>

    {{-- ============ Step 1: upload the Word template ============ --}}
    <div class="mb-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-900">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">1</span>
            {{ __('orders::order_composer.designer.upload_title') }}
        </div>

        <div class="rounded-xl bg-blue-50/60 px-4 py-3 text-[12px] leading-relaxed text-zinc-600">
            {!! __('orders::order_composer.designer.upload_hint') !!}
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-zinc-300 bg-zinc-50/60 px-4 py-2.5 text-[13px] font-medium text-zinc-600 transition hover:border-zinc-900 hover:bg-zinc-50 hover:text-zinc-900">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>{{ __('orders::order_composer.designer.choose_file') }}</span>
                <input type="file" wire:model="upload" accept=".docx,.doc" class="hidden">
            </label>

            <div wire:loading wire:target="upload" class="flex items-center gap-2 text-[12px] text-zinc-400">
                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                {{ __('orders::order_composer.designer.parsing') }}
            </div>

            @if ($originalFileName !== '')
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-[12px] font-medium text-emerald-700">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    {{ $originalFileName }}
                </span>
            @endif
        </div>
        @error('upload') <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- ============ Effect: what happens on approval ============ --}}
    <div class="mb-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <label class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
            <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ __('orders::order_composer.designer.effect_title') }}
        </label>
        <p class="mb-3 mt-1 text-[12px] text-zinc-400">{{ __('orders::order_composer.designer.effect_hint') }}</p>
        <select wire:model.live="effect"
            class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-2.5 text-[13px] text-zinc-800 focus:border-zinc-400 focus:ring-0 sm:max-w-md">
            @foreach ($this->effectOptions as $opt)
                <option value="{{ $opt['kind'] }}">{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- ============ Step 2: map the detected variables ============ --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="mb-1 flex items-center gap-2 text-sm font-semibold text-zinc-900">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-900 text-xs text-white">2</span>
            {{ __('orders::order_composer.designer.variables') }}
            @if (count($variables))
                <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-500">{{ count($variables) }}</span>
            @endif
        </div>
        <p class="mb-4 text-[12px] text-zinc-400">{{ __('orders::order_composer.designer.variables_hint') }}</p>

        @if (count($variables) === 0)
            <div class="rounded-xl border border-dashed border-zinc-200 py-10 text-center text-[13px] text-zinc-400">
                {{ __('orders::order_composer.designer.no_variables_yet') }}
            </div>
        @else
            <div class="space-y-2.5">
                @foreach ($variables as $i => $v)
                    <div wire:key="var-{{ $i }}"
                        class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50/40 p-3 sm:flex-row sm:items-center">
                        {{-- placeholder label --}}
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-900/90 text-[11px] font-mono text-white">{{ $i + 1 }}</span>
                            <code class="truncate rounded-md bg-white px-2 py-1 text-[13px] font-medium text-zinc-800 ring-1 ring-zinc-200">[{{ $v['label'] }}]</code>
                        </div>

                        {{-- source toggle --}}
                        <div class="inline-flex shrink-0 rounded-lg bg-zinc-100 p-0.5">
                            <button type="button" wire:click="$set('variables.{{ $i }}.source', 'auto')"
                                class="rounded-md px-3 py-1 text-[12px] font-medium transition {{ $v['source'] === 'auto' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                {{ __('orders::order_composer.designer.auto') }}
                            </button>
                            <button type="button" wire:click="$set('variables.{{ $i }}.source', 'manual')"
                                class="rounded-md px-3 py-1 text-[12px] font-medium transition {{ $v['source'] === 'manual' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}">
                                {{ __('orders::order_composer.designer.manual') }}
                            </button>
                        </div>

                        {{-- mapping target --}}
                        <div class="w-full sm:w-72 sm:shrink-0">
                            @if ($v['source'] === 'auto')
                                <select wire:model="variables.{{ $i }}.auto_key"
                                    class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-2.5 text-[13px] text-zinc-800 focus:border-zinc-400 focus:ring-0">
                                    <option value="">— {{ __('orders::order_composer.designer.choose_source') }} —</option>
                                    @foreach ($this->variableGroups as $group => $vars)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($vars as $rv)
                                                <option value="{{ $rv['key'] }}">{{ $rv['label'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            @else
                                <select wire:model="variables.{{ $i }}.field_type"
                                    class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-2.5 text-[13px] text-zinc-800 focus:border-zinc-400 focus:ring-0">
                                    @foreach ($this->fieldTypes as $ft)
                                        <option value="{{ $ft['type'] }}">{{ $ft['label'] }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error("variables.$i.auto_key") <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- effect role: only for manual fields when the order has an HR effect --}}
                        @if ($effect !== 'none' && $v['source'] === 'manual' && count($this->effectRoles))
                            <div class="w-full sm:w-56 sm:shrink-0">
                                <select wire:model="variables.{{ $i }}.effect_role"
                                    class="h-9 w-full rounded-lg border border-dashed border-zinc-300 bg-white px-2.5 text-[13px] text-zinc-700 focus:border-zinc-400 focus:ring-0">
                                    <option value="">— {{ __('orders::order_composer.designer.effect_role_none') }} —</option>
                                    @foreach ($this->effectRoles as $role)
                                        <option value="{{ $role['key'] }}">{{ $role['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============ View / correct the prepared template ============ --}}
    @if ($docxPath !== null && $upload === null)
        <div class="mt-5 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('orders::order_composer.designer.template_view_title') }}</h3>
                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="previewTemplate" wire:loading.attr="disabled" wire:target="previewTemplate"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-[13px] font-medium text-zinc-700 transition hover:border-zinc-400 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="previewTemplate" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg wire:loading wire:target="previewTemplate" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                        {{ __('orders::order_composer.designer.view_template') }}
                    </button>
                    <button type="button" wire:click="downloadTemplate"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-[13px] font-medium text-zinc-700 transition hover:border-zinc-400">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        {{ __('orders::order_composer.designer.edit_in_word') }}
                    </button>
                </div>
            </div>
            <p class="mt-1.5 text-[12px] text-zinc-400">{{ __('orders::order_composer.designer.template_view_hint') }}</p>
            @error('templatePdf') <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p> @enderror

            <div wire:loading.flex wire:target="previewTemplate" class="mt-3 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 py-10 text-sm text-zinc-400">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle class="opacity-25" cx="12" cy="12" r="10"/><path class="opacity-75" d="M4 12a8 8 0 018-8"/></svg>
                {{ __('orders::order_composer.designer.view_template') }}…
            </div>
            @if ($templatePdf !== '')
                <div wire:loading.remove wire:target="previewTemplate" class="mt-3 overflow-hidden rounded-xl border border-zinc-200 shadow-inner">
                    <iframe src="data:application/pdf;base64,{{ $templatePdf }}" class="h-[70vh] w-full" title="template-preview"></iframe>
                </div>
            @endif

            {{-- version history --}}
            @if (count($this->versions))
                <div class="mt-4 border-t border-zinc-100 pt-3">
                    <div class="mb-2 flex items-center gap-2 text-[12px] font-semibold text-zinc-700">
                        <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/></svg>
                        {{ __('orders::order_composer.designer.versions_title') }}
                        <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-500">{{ count($this->versions) }}</span>
                    </div>
                    <div class="space-y-1">
                        @foreach ($this->versions as $ver)
                            <div wire:key="ver-{{ $ver->id }}" class="flex items-center gap-2 rounded-lg bg-zinc-50/60 px-3 py-1.5 text-[12px]">
                                <span class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] font-medium text-zinc-600 ring-1 ring-zinc-200">v{{ $ver->version }}</span>
                                <span class="truncate text-zinc-600">{{ $ver->label }}</span>
                                <span class="text-zinc-300">·</span>
                                <span class="text-zinc-400">{{ optional($ver->created_at)->format('d.m.Y H:i') }}</span>
                                <button type="button" wire:click="downloadVersion({{ $ver->id }})"
                                    class="ml-auto inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium text-zinc-500 transition hover:bg-white hover:text-zinc-900">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    {{ __('orders::order_composer.designer.edit_in_word') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>
