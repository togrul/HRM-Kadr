@props([
  'label' => '',
  'placeholder' => '---',
  'mode' => 'default',
  'disabled' => false,
  'model' => [],  // [{id,label}]
  'selectedLabel' => null,
  'loadOnOpen' => null,
  'searchModel' => null,
  'searchPlaceholder' => null,
  'direction' => 'auto',
  'instance' => null,
])

@php
  use Illuminate\Support\Str;
  $wireModelKeys = ['wire:model.live', 'wire:model.blur', 'wire:model.lazy', 'wire:model.defer', 'wire:model'];
  $wireModel = collect($wireModelKeys)
      ->map(fn ($key) => $attributes->get($key))
      ->first(fn ($value) => filled($value));
  $identitySource = (string) ($instance
      ?? $wireModel
      ?? $attributes->get('name')
      ?? $attributes->get('id')
      ?? $searchModel
      ?? $label
      ?? 'select-dropdown');
  $uid = 'ui-select-'.substr(md5($identitySource.'|'.$searchModel.'|'.$label), 0, 12);
  $labelId = $uid.'-label';
  $bg = $mode === 'gray' ? 'bg-neutral-100' : 'bg-white';
@endphp

<div
  x-data="{
    uid: @js($uid),
    currentValue: @if($wireModel) @entangle($wireModel).live @else null @endif,
    lastValue: @if($wireModel) @js(null) @else null @endif,
    localSearch: '',
    cachedOptions: @js($model),
    placeholder: @js($placeholder),
    isOpen: false,
    openUp: false,
    panelMaxHeight: 224,
    panelStyles: {},
    preferredDirection: @js($direction),
    isDisabled: @js((bool) $disabled),
    loadOnOpen: @js($loadOnOpen),
    pendingReopen: false,
    pendingSelectionClose: false,
    selectedCache: { id: null, label: '' },
    initialSelectedLabel: @js($selectedLabel),
    toId(v){ return (v===null||v===undefined||v==='') ? null : String(v).trim(); },
    toWireValue(v){
      if (v===null || v===undefined || v==='') return null;
      const s = String(v).trim();
      return /^[0-9]+$/.test(s) ? Number(s) : s;
    },
    optionLabel(option){
      if (!option) return '';
      return option.label ?? option.name ?? option.title ?? option.text ?? '';
    },
    optionId(option){
      if (!option) return null;
      return option.id ?? option.value ?? null;
    },
    normalizeOptions(options){
      return (Array.isArray(options) ? options : [])
        .map((option) => {
          const id = this.optionId(option);
          const normalizedId = this.toId(id);
          if (normalizedId === null) return null;

          return {
            ...option,
            id: normalizedId,
            label: String(this.optionLabel(option) ?? '').trim(),
          };
        })
        .filter(Boolean);
    },
    normalizeSearchValue(value){
      return String(value ?? '')
        .toLocaleLowerCase('az')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/ə/g, 'e')
        .replace(/ı/g, 'i')
        .replace(/ö/g, 'o')
        .replace(/ü/g, 'u')
        .replace(/ş/g, 's')
        .replace(/ç/g, 'c')
        .replace(/ğ/g, 'g');
    },
    matchesSearch(label){
      const query = this.normalizeSearchValue(this.localSearch).trim();
      if (query === '') return true;
      return this.normalizeSearchValue(label).includes(query);
    },
    syncSelectedCache(currentId = this.toId(this.currentValue)){
      const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
      if (found) {
        this.selectedCache = { id: this.toId(found.id), label: found.label };
        return;
      }
      if (currentId === null) {
        this.selectedCache = { id: null, label: '' };
      }
    },
    syncOptionsFromDom(){
      const optionNodes = Array.from(this.$root.querySelectorAll('[data-option-id]'));
      const domOptions = optionNodes.map((node) => ({
        id: node.dataset.optionId,
        label: node.dataset.optionLabel ?? '',
      }));
      this.cachedOptions = this.normalizeOptions(domOptions);
      this.syncSelectedCache();
    },
    observeOptions(){
      const target = this.$refs.panel ?? this.$root;
      if (!target || typeof MutationObserver === 'undefined') return;

      const observer = new MutationObserver(() => {
        this.$nextTick(() => {
          this.syncOptionsFromDom();
          if (this.isOpen) {
            requestAnimationFrame(() => this.repositionPanel());
          }
        });
      });

      observer.observe(target, {
        childList: true,
        subtree: true,
      });

      this.$root._uiSelectObserver = observer;
    },

    init(){
      this.cachedOptions = this.normalizeOptions(this.cachedOptions);
      this.lastValue = this.toId(this.currentValue);
      const currentId = this.toId(this.currentValue);
      if (this.initialSelectedLabel && currentId !== null) {
        const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
        if (!found) {
          this.selectedCache = { id: currentId, label: this.initialSelectedLabel };
        }
      }
      this.$nextTick(() => {
        this.syncOptionsFromDom();
        this.observeOptions();
      });
      this.$watch('currentValue', (next) => {
        const normalizedNext = this.toId(next);
        this.syncSelectedCache(normalizedNext);
        if (this.pendingSelectionClose || normalizedNext !== this.lastValue) {
          this.pendingSelectionClose = false;
          this.isOpen = false;
        }
        this.lastValue = normalizedNext;
        if (this.isOpen) {
          this.$nextTick(() => requestAnimationFrame(() => this.repositionPanel()));
        }
      });
    },

    setOpen(next){
      this.isOpen = !!next;
    },

    repositionPanel(){
      const button = this.$refs.button;
      const panel = this.$refs.panel;
      if (!button || !panel) return;

      const buttonRect = button.getBoundingClientRect();
      const viewportHeight = window.visualViewport?.height || window.innerHeight;
      const viewportWidth = window.visualViewport?.width || window.innerWidth;
      const gap = 8;
      const viewportPadding = 12;
      const naturalHeight = Math.min(panel.scrollHeight || 224, 320);
      const availableBelow = Math.max(140, viewportHeight - buttonRect.bottom - gap - viewportPadding);
      const availableAbove = Math.max(140, buttonRect.top - gap - viewportPadding);

      if (this.preferredDirection === 'up') {
        this.openUp = true;
      } else if (this.preferredDirection === 'down') {
        this.openUp = false;
      } else {
        this.openUp = naturalHeight > availableBelow && availableAbove > availableBelow;
      }
      this.panelMaxHeight = this.openUp
        ? Math.min(320, availableAbove)
        : Math.min(320, availableBelow);

      const panelHeight = Math.min(naturalHeight, this.panelMaxHeight);
      const desiredWidth = Math.max(buttonRect.width, 220);
      const clampedWidth = Math.min(desiredWidth, viewportWidth - (viewportPadding * 2));
      const maxLeft = Math.max(viewportPadding, viewportWidth - clampedWidth - viewportPadding);
      const left = Math.min(Math.max(buttonRect.left, viewportPadding), maxLeft);
      const top = this.openUp
        ? Math.max(viewportPadding, buttonRect.top - gap - panelHeight)
        : Math.min(buttonRect.bottom + gap, viewportHeight - panelHeight - viewportPadding);

      this.panelStyles = {
        position: 'fixed',
        top: `${Math.round(top)}px`,
        left: `${Math.round(left)}px`,
        width: `${Math.round(clampedWidth)}px`,
        maxHeight: `${Math.round(this.panelMaxHeight)}px`,
      };
    },

    selectedLabel(){
      const currentId = this.toId(this.currentValue);
      if (currentId == null || currentId === '') return this.placeholder;
      const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
      if (found) return found.label;
      if (this.toId(this.selectedCache.id) === currentId && this.selectedCache.label) {
        return this.selectedCache.label;
      }
      if (this.initialSelectedLabel && currentId !== null) {
        return this.initialSelectedLabel;
      }
      return this.placeholder;
    },

    select(id, label = null){
      const wireValue = this.toWireValue(id);
      const val = this.toId(wireValue);
      this.pendingReopen = false;
      this.pendingSelectionClose = true;
      if (label !== null && label !== undefined) {
        this.selectedCache = { id: val, label: String(label) };
      } else {
        const found = this.cachedOptions.find(o => this.toId(o.id) === val);
        this.selectedCache = found ? { id: this.toId(found.id), label: found.label } : { id: val, label: '' };
      }
      this.currentValue = wireValue;
      this.initialSelectedLabel = null;
      this.isOpen = false;
      queueMicrotask(() => { this.isOpen = false; });
      requestAnimationFrame(() => { this.isOpen = false; });
      setTimeout(() => { this.isOpen = false; }, 0);
    },

    toggle(){
      if (this.isDisabled) return;
      if (this.isOpen) {
        this.setOpen(false);
        return;
      }

      this.setOpen(true);
      window.dispatchEvent(new CustomEvent('ui-select-opened', { detail: { uid: this.uid } }));
      this.$nextTick(() => {
        requestAnimationFrame(() => this.repositionPanel());
      });
      if (this.isOpen && this.loadOnOpen && $wire && typeof $wire.loadOptionGroup === 'function') {
        this.pendingReopen = true;
        $wire.loadOptionGroup(this.loadOnOpen);
      }
    },
  }"
  x-on:click.window="if (!$el.contains($event.target)) setOpen(false)"
  x-on:keydown.escape.window="setOpen(false)"
  x-on:ui-select-opened.window="if ($event.detail?.uid !== uid) setOpen(false)"
  x-on:ui-select-option-group-loaded.window="
    if ($event.detail?.group !== loadOnOpen || !pendingReopen) return;
    pendingReopen = false;
    setOpen(true);
    $nextTick(() => requestAnimationFrame(() => repositionPanel()));
  "
  x-on:resize.window.debounce.100ms="if (isOpen) repositionPanel()"
  x-on:scroll.window.debounce.50ms="if (isOpen) repositionPanel()"
  {{ $attributes->except(['wire:model','wire:model.live','wire:model.defer','wire:model.lazy','wire:model.blur'])->class('relative isolate w-full') }}
  x-bind:class="isOpen ? 'z-[900]' : 'z-10'"
>
  @if($label)
    <x-label id="{{ $labelId }}" for="{{ $uid }}">{{ $label }}</x-label>
  @endif

  <div class="relative mt-1">
    <button
      type="button" id="{{ $uid }}-button"
      x-ref="button"
      class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $bg }} {{ $disabled ? 'opacity-60 cursor-not-allowed' : '' }}"
      :aria-expanded="isOpen" aria-labelledby="{{ $labelId }}"
      :disabled="isDisabled"
      x-on:click.prevent.stop="toggle()"
    >
      <span class="flex items-center">
        <span class="block ml-3 font-normal truncate text-neutral-900" x-text="selectedLabel()">{{ $placeholder }}</span>
      </span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
        <svg class="w-5 h-5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </span>
    </button>

    <ul
      x-ref="panel"
      x-show="isOpen && !isDisabled" x-transition.opacity.duration.100ms x-cloak
      :class="openUp ? 'origin-bottom' : 'origin-top'"
      :style="panelStyles"
      class="z-[910] px-3 py-2 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl focus:outline-none sm:text-sm"
    >
      {{-- slot: search input --}}
      @if ($searchModel)
        <li class="sticky top-0 bg-white pt-1 pb-2 z-20">
          <div class="px-1">
            <x-livewire-input
              mode="gray"
              :name="$searchModel"
              wire:model.live.debounce.300ms="{{ $searchModel }}"
              x-model.live.debounce.150ms="localSearch"
              placeholder="{{ $searchPlaceholder ?? __('ui::common.placeholders.search') }}"
              x-on:click.stop="$event.stopPropagation()"
              x-on:focus.stop="setOpen(true)"
              x-on:input.stop="setOpen(true)"
              x-on:keyup.stop="setOpen(true)"
              x-on:keydown.stop="setOpen(true)"
              x-on:change.stop="null"
            />
          </div>
        </li>
      @elseif (isset($slot) && ! $slot->isEmpty())
        <li class="sticky top-0 bg-white pt-1 pb-2 z-20">
          <div class="px-1">
            {{ $slot }}
          </div>
        </li>
      @endif

      {{-- null/placeholder option --}}
      <li class="relative py-2 pl-3 rounded-lg cursor-pointer select-none group pr-9 hover:bg-blue-400 bg-neutral-50"
          x-show="matchesSearch(placeholder)"
          x-on:click.prevent.stop="select(null, placeholder)">
        <div class="flex items-center">
          <span class="block ml-3 truncate"> {{ $placeholder }} </span>
          <span
            x-show="toId(currentValue) === null"
            class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600"
          >
            ✓
          </span>
        </div>
      </li>

      @foreach($model as $idx => $opt)
        <li
          wire:key="{{ $uid }}-{{ data_get($opt,'id') }}"
          class="relative py-2 pl-3 rounded-lg cursor-pointer select-none group pr-9 hover:bg-blue-400 bg-neutral-50"
          data-option-id="{{ data_get($opt,'id') }}"
          data-option-label="{{ data_get($opt,'label', data_get($opt,'name', data_get($opt,'title', data_get($opt,'text')))) }}"
          x-show="matchesSearch($el.dataset.optionLabel)"
          x-on:click.prevent.stop="select($el.dataset.optionId, $el.dataset.optionLabel)"
        >
          <div class="flex items-center">
            <span class="block ml-3 truncate">{{ data_get($opt,'label', data_get($opt,'name', data_get($opt,'title', data_get($opt,'text')))) }}</span>
            <span
              x-show="toId(currentValue) === toId(@js(data_get($opt,'id')))"
              class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600"
            >
              ✓
            </span>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</div>
