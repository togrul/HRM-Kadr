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

  // Without a key Livewire's morph may REPLACE this root on a parent re-render — and the
  // panel lives in an x-teleport, so replacing the root tears the panel out and resets
  // isOpen. That is why typing in the search box closed the dropdown. A stable key makes
  // Livewire patch the element instead, so the Alpine state survives the round trip.
  $rootKey = (string) ($attributes->get('wire:key') ?: $uid);
@endphp

<div
  x-data="{
    ...window.uiSelectDropdown({
      uid: @js($uid),
      placeholder: @js($placeholder),
      preferredDirection: @js($direction),
      isDisabled: @js((bool) $disabled),
      loadOnOpen: @js($loadOnOpen),
    }),
    @if($wireModel) currentValue: @entangle($wireModel).live, @endif
  }"
  x-on:click.window="if (!$el.contains($event.target) && !($refs.panel && $refs.panel.contains($event.target))) setOpen(false)"
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
  wire:key="{{ $rootKey }}"
  data-selected-label="{{ $selectedLabel }}"
  {{ $attributes->except(['wire:key','wire:model','wire:model.live','wire:model.defer','wire:model.lazy','wire:model.blur'])->class('relative isolate w-full') }}
  x-bind:class="isOpen ? 'z-[900]' : 'z-10'"
>
  @if($label)
    <x-label id="{{ $labelId }}" for="{{ $uid }}">{{ $label }}</x-label>
  @endif

  <div class="relative mt-1">
    <button
      type="button" id="{{ $uid }}-button"
      x-ref="button"
      class="{{ \App\Support\Ui\FieldStyles::select('relative flex items-center text-left') }} {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }}"
      :aria-expanded="isOpen" aria-labelledby="{{ $labelId }}"
      :disabled="isDisabled"
      x-on:click.prevent.stop="toggle()"
    >
      <span class="flex items-center">
        <span class="block truncate text-ink" x-text="selectedLabel()">{{ $placeholder }}</span>
      </span>
      <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
        <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
        </svg>
      </span>
    </button>

    <template x-teleport="body">
      <ul
        x-ref="panel"
        x-show="isOpen && positioned && !isDisabled" x-transition.opacity.duration.100ms x-cloak
        :class="openUp ? 'origin-bottom' : 'origin-top'"
        :style="panelStyles"
        class="hrm-scroll fixed z-[9999] space-y-0.5 overflow-auto rounded-xl border border-hairline bg-white p-1 text-[12.5px] shadow-overlay focus:outline-none"
      >
        {{-- slot: search input --}}
        @if ($searchModel)
          <li class="sticky top-0 z-20 bg-white px-0.5 pb-1.5 pt-0.5">
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
          <li class="sticky top-0 z-20 bg-white px-0.5 pb-1.5 pt-0.5">
            <div class="px-1">
              {{ $slot }}
            </div>
          </li>
        @endif

        {{-- null/placeholder option --}}
        <li class="group hrm-select-option"
            x-show="matchesSearch(placeholder)"
            x-on:click.prevent.stop="select(null, placeholder)">
          <div class="flex items-center">
            <span class="block truncate">{{ $placeholder }}</span>
            <span
              x-show="toId(currentValue) === null"
              class="hrm-select-check"
            >
              ✓
            </span>
          </div>
        </li>

        @foreach($model as $idx => $opt)
          <li
            wire:key="{{ $uid }}-{{ data_get($opt,'id') }}"
            class="group hrm-select-option"
            data-option-id="{{ data_get($opt,'id') }}"
            data-option-label="{{ data_get($opt,'label', data_get($opt,'name', data_get($opt,'title', data_get($opt,'text')))) }}"
            x-show="matchesSearch($el.dataset.optionLabel)"
            x-on:click.prevent.stop="select($el.dataset.optionId, $el.dataset.optionLabel)"
          >
            <div class="flex items-center">
              <span class="block truncate">{{ data_get($opt,'label', data_get($opt,'name', data_get($opt,'title', data_get($opt,'text')))) }}</span>
              <span
                x-show="toId(currentValue) === toId(@js(data_get($opt,'id')))"
                class="hrm-select-check"
              >
                ✓
              </span>
            </div>
          </li>
        @endforeach
      </ul>
    </template>
  </div>
</div>
