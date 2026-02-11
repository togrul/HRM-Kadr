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
  $wireModelKeys = ['wire:model.live', 'wire:model.blur', 'wire:model.lazy', 'wire:model.defer', 'wire:model'];
  $wireModel = collect($wireModelKeys)
      ->map(fn ($key) => $attributes->get($key))
      ->first(fn ($value) => filled($value));
  $uid = 'ui-select-'.Str::slug($wireModel ?? Str::uuid(), '_');
  $labelId = $uid.'-label';
  $bg = $mode === 'gray' ? 'bg-neutral-100' : 'bg-white';
@endphp

<div
  wire:key="select-{{ $uid }}"
  x-data="{
    currentValue: @if($wireModel) @entangle($wireModel).live @else null @endif,
    cachedOptions: @js($model),
    placeholder: @js($placeholder),
    isOpen: false,
    isDisabled: @js((bool) $disabled),
    selectedCache: { id: null, label: '' },
    initialSelectedLabel: @js($selectedLabel),
    toId(v){ return (v===null||v===undefined||v==='') ? null : String(v).trim(); },
    toWireValue(v){
      if (v===null || v===undefined || v==='') return null;
      const s = String(v).trim();
      return /^[0-9]+$/.test(s) ? Number(s) : s;
    },

    init(){
      const currentId = this.toId(this.currentValue);
      if (this.initialSelectedLabel && currentId !== null) {
        const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
        if (!found) {
          this.selectedCache = { id: currentId, label: this.initialSelectedLabel };
        }
      }
      this.$watch('currentValue', (next) => {
        const nextId = this.toId(next);
        const found = this.cachedOptions.find(o => this.toId(o.id) === nextId);
        if (found) {
          this.selectedCache = { id: this.toId(found.id), label: found.label };
        }
      });
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
      if (label !== null && label !== undefined) {
        this.selectedCache = { id: val, label: String(label) };
      } else {
        const found = this.cachedOptions.find(o => this.toId(o.id) === val);
        this.selectedCache = found ? { id: this.toId(found.id), label: found.label } : { id: val, label: '' };
      }
      this.currentValue = wireValue;
      this.initialSelectedLabel = null;
      this.isOpen = false;
    },

    toggle(){
      if (this.isDisabled) return;
      this.isOpen = !this.isOpen;
    },
  }"
  @click.window="if (!$el.contains($event.target)) isOpen = false"
  @keydown.escape.window="isOpen = false"
  {{ $attributes->except(['wire:model','wire:model.live','wire:model.defer','wire:model.lazy','wire:model.blur'])->class('w-full') }}
>
  @if($label)
    <x-label id="{{ $labelId }}" for="{{ $uid }}">{{ $label }}</x-label>
  @endif

  <div class="relative mt-1">
    <button
      type="button" id="{{ $uid }}-button"
      class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $bg }} {{ $disabled ? 'opacity-60 cursor-not-allowed' : '' }}"
      :aria-expanded="isOpen" aria-labelledby="{{ $labelId }}"
      :disabled="isDisabled"
      @click.prevent.stop="toggle()"
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
      x-show="isOpen && !isDisabled" x-transition.opacity.duration.100ms x-cloak
      class="absolute z-10 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm"
    >
      {{-- slot: search input --}}
      {{ $slot }}

      {{-- null/placeholder option --}}
      <li class="relative py-2 pl-3 rounded-lg cursor-pointer select-none group pr-9 hover:bg-blue-400 bg-neutral-50"
          @click.prevent.stop="select(null, placeholder)">
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
          data-option-label="{{ data_get($opt,'label') }}"
          @click.prevent.stop="select($el.dataset.optionId, $el.dataset.optionLabel)"
        >
          <div class="flex items-center">
            <span class="block ml-3 truncate">{{ data_get($opt,'label') }}</span>
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
