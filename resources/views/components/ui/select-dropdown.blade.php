@props([
  'label' => '',
  'placeholder' => '---',
  'mode' => 'default',
  'disabled' => false,
  'model' => [],  // [{id,label}]
  'selectedLabel' => null,
])

@php
  use Illuminate\Support\Str;
  $wireModelDirective = $attributes->wire('model'); // 'filter.structure_id' vb.
  $wireModel = optional($wireModelDirective)->value();
  $uid = 'ui-select-'.Str::slug($wireModel ?? Str::uuid(), '_');
  $labelId = $uid.'-label';
  $bg = $mode === 'gray' ? 'bg-neutral-100' : 'bg-white';
@endphp

<div
  wire:key="select-{{ $uid }}"
  x-data="{
    valueProxy: @if($wireModel) @entangle($wireModel).live @else null @endif,
    cachedOptions: @js($model),
    placeholder: @js($placeholder),
    uid: @js($uid),
    isOpen: false,
    currentValue: null,
    selectedCache: { id: null, label: '' },
    initialSelectedLabel: @js($selectedLabel),

    resolve(t){
      if (!t) return t;
      if (typeof t.get === 'function') return t.get();
      if (typeof t.value !== 'undefined') return t.value;
      return t;
    },
    toId(v){ return (v===null||v===undefined||v==='') ? null : String(v); },

    init(){
      this.syncValue();
      if (this.initialSelectedLabel && this.currentValue !== null) {
        const found = this.cachedOptions.find(o => String(o.id) === String(this.currentValue));
        if (!found) {
          this.selectedCache = { id: this.currentValue, label: this.initialSelectedLabel };
        }
      }
      this.$watch(() => this.resolve(this.valueProxy), () => this.syncValue());
    },

    // when Livewire re-renders, Blade re-passes :model — Alpine sees it via x-bind below
    applyOptions(next){
      if (!Array.isArray(next)) return;
      // replace, preserve selection label if missing
      this.cachedOptions = next;
      // try refresh cache if selected exists in new list
      const found = this.cachedOptions.find(o => String(o.id) === String(this.currentValue));
      if (found) this.selectedCache = { id: found.id, label: found.label };
    },

    syncValue(){
      const next = this.toId(this.resolve(this.valueProxy));
      if (next === this.currentValue) return;
      this.currentValue = next;
      // try to find label in current list
      const found = this.cachedOptions.find(o => String(o.id) === String(this.currentValue));
      if (found) {
        this.selectedCache = { id: found.id, label: found.label };
      }
      // else keep previous selectedCache (so text stays) until server returns item
    },

    selectedLabel(){
      if (this.currentValue == null || this.currentValue === '') return this.placeholder;
      const found = this.cachedOptions.find(o => String(o.id) === String(this.currentValue));
      if (found) return found.label;
      if (String(this.selectedCache.id) === String(this.currentValue) && this.selectedCache.label) {
        return this.selectedCache.label;
      }
      if (this.initialSelectedLabel && this.currentValue !== null) {
        return this.initialSelectedLabel;
      }
      return this.placeholder;
    },

    select(id, label = null){
      const val = this.toId(id);
      this.currentValue = val;
      if (label !== null && label !== undefined) {
        this.selectedCache = { id: val, label: String(label) };
      } else {
        const found = this.cachedOptions.find(o => String(o.id) === String(val));
        this.selectedCache = found ? { id: found.id, label: found.label } : { id: val, label: '' };
      }
      if (this.valueProxy && typeof this.valueProxy.set === 'function') {
        this.valueProxy.set(val);
      } else {
        this.valueProxy = val;
      }
      this.initialSelectedLabel = null;
      this.isOpen = false;
    },

    toggle(){ this.isOpen = !this.isOpen; },
  }"
  @click.window="if (!$el.contains($event.target)) isOpen = false"
  @keydown.escape.window="isOpen = false"
  {{ $attributes->except(['wire:model','wire:model.defer','wire:model.lazy'])->class('w-full') }}
>
  @if($label)
    <x-label id="{{ $labelId }}" for="{{ $uid }}">{{ $label }}</x-label>
  @endif

  <div class="relative mt-1">
    <button
      type="button" id="{{ $uid }}-button"
      class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $bg }}"
      :aria-expanded="isOpen" aria-labelledby="{{ $labelId }}"
      @click="toggle()"
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
      x-show="isOpen" x-transition.opacity.duration.100ms x-cloak
      class="absolute z-10 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm"
    >
      {{-- slot: search input --}}
      {{ $slot }}

      {{-- null/placeholder option --}}
      <li class="relative py-2 pl-3 rounded-lg cursor-pointer select-none group pr-9 hover:bg-blue-400 bg-neutral-50"
          @click="select(null, placeholder)">
        <div class="flex items-center">
          <span class="block ml-3 truncate"> {{ $placeholder }} </span>
        </div>
      </li>

      @foreach($model as $idx => $opt)
        <li
          wire:key="{{ $uid }}-{{ data_get($opt,'id') }}"
          class="relative py-2 pl-3 rounded-lg cursor-pointer select-none group pr-9 hover:bg-blue-400 bg-neutral-50"
          @click="select('{{ data_get($opt,'id') }}', '{{ e(data_get($opt,'label')) }}')"
        >
          <div class="flex items-center">
            <span class="block ml-3 truncate">{{ data_get($opt,'label') }}</span>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</div>
