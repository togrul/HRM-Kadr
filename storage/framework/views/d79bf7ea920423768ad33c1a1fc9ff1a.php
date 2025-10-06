
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
  'label' => '',
  'options' => [],
  'placeholder' => '---',
  'mode' => 'default',      // 'default' | 'gray'
  'disabled' => false,
  'hasCheckbox' => false,
  'nullValue' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
  'label' => '',
  'options' => [],
  'placeholder' => '---',
  'mode' => 'default',      // 'default' | 'gray'
  'disabled' => false,
  'hasCheckbox' => false,
  'nullValue' => null,
]); ?>
<?php foreach (array_filter(([
  'label' => '',
  'options' => [],
  'placeholder' => '---',
  'mode' => 'default',      // 'default' | 'gray'
  'disabled' => false,
  'hasCheckbox' => false,
  'nullValue' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
  use Illuminate\Support\Str;

  $wireModel = $attributes->wire('model')->value();

  // Normalize options: supports ['1'=>'Label'] or [['id'=>1,'label'=>'Label'], ...]
  $normalized = collect($options)->map(function ($opt, $key) {
      if (is_array($opt) || is_object($opt)) {
          return ['id' => data_get($opt, 'id', $key), 'label' => data_get($opt, 'label', (string)$key)];
      }
      return ['id' => $key, 'label' => $opt];
  })->values();

  $bg = match($mode) {
    'default' => 'bg-white',
    'gray'    => 'bg-neutral-100',
    default   => 'bg-white',
  };

  $hasError = $wireModel ? $errors->has($wireModel) : false;

  // Stable ids/keys
  $uid = 'ui-select-'.Str::slug($wireModel ?? Str::uuid(), '_');
  $labelId = $uid.'-label';
?>

<div
  x-data="{
    id: <?php echo \Illuminate\Support\Js::from($uid)->toHtml() ?>,
    isOpen: false,
    value: <?php if ((object) ($wireModel) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel->value()); ?>')<?php echo e($wireModel->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel); ?>')<?php endif; ?>,
    options: <?php echo \Illuminate\Support\Js::from($normalized)->toHtml() ?>,
    placeholder: <?php echo \Illuminate\Support\Js::from($placeholder)->toHtml() ?>,
    disabled: <?php echo \Illuminate\Support\Js::from($disabled)->toHtml() ?>,
    activeIndex: -1,
    nullValue: <?php echo \Illuminate\Support\Js::from($nullValue)->toHtml() ?>,

    toggle(){ if (this.disabled) return; this.isOpen = !this.isOpen; if (this.isOpen) { this.setActiveToCurrent(); this.$nextTick(()=> this.$refs.listbox?.focus()) } },
    openAndMove(step){ if (this.disabled) return; this.isOpen = true; this.$nextTick(()=> this.$refs.listbox?.focus()); this.move(step) },
    move(step){ if (!this.options.length) return; if (this.activeIndex === -1) this.setActiveToCurrent(); this.activeIndex = (this.activeIndex + step + this.options.length) % this.options.length; this.scrollActiveIntoView() },
    commitActive(){ if (this.activeIndex >= 0) this.select(this.options[this.activeIndex].id) },
    setActiveToCurrent(){ const i = this.options.findIndex(o => String(o.id) === String(this.value)); this.activeIndex = i >= 0 ? i : 0 },
    select(id){ this.value = id; this.isOpen = false },
    selectedLabel(){ const f = this.options.find(o => String(o.id) === String(this.value)); return f ? f.label : this.placeholder },
    scrollActiveIntoView(){ const el = document.getElementById(this.id + '-option-' + this.activeIndex); if (el) el.scrollIntoView({ block: 'nearest' }) }
  }"
  
  @click.window="if (!$el.contains($event.target)) isOpen = false"
  @keydown.escape.window="isOpen = false"
  wire:key="<?php echo e($uid); ?>"
  <?php echo e($attributes->except(['wire:model','wire:model.defer','wire:model.lazy'])->class('w-full')); ?>

>
  <!--[if BLOCK]><![endif]--><?php if($hasCheckbox): ?>
    <div class="flex items-center justify-between space-x-2">
      <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['id' => ''.e($labelId).'','for' => ''.e($uid).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => ''.e($labelId).'','for' => ''.e($uid).'']); ?><?php echo e($label); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
      <!--[if BLOCK]><![endif]--><?php if(isset($checkbox)): ?> <?php echo e($checkbox); ?> <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
  <?php else: ?>
    <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['id' => ''.e($labelId).'','for' => ''.e($uid).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => ''.e($labelId).'','for' => ''.e($uid).'']); ?><?php echo e($label); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
  <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

  <div class="relative mt-1">
    <button
      type="button"
      id="<?php echo e($uid); ?>-button"
      class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo e($bg); ?> <?php echo e($disabled ? 'opacity-60 cursor-not-allowed' : ''); ?> <?php echo e($hasError ? 'bg-rose-50' : ''); ?>"
      :aria-expanded="isOpen"
      aria-labelledby="<?php echo e($labelId); ?>"
      :aria-controls="id + '-listbox'"
      :disabled="disabled"
      @click="toggle()"
      @keydown.arrow-down.prevent="openAndMove(1)"
      @keydown.arrow-up.prevent="openAndMove(-1)"
      @keydown.enter.prevent="commitActive()"
      @keydown.space.prevent="toggle()"
    >
      <span class="flex items-center">
        <span class="block ml-3 font-normal text-neutral-900 truncate"
              x-text="selectedLabel()"><?php echo e($placeholder); ?></span>
      </span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
        <svg class="w-5 h-5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </span>
    </button>

    <!--[if BLOCK]><![endif]--><?php if (! ($disabled)): ?>
      <ul
        x-ref="listbox"
        x-show="isOpen"
        x-transition.opacity.duration.100ms
        x-cloak
        :id="id + '-listbox'"
        role="listbox"
        aria-labelledby="<?php echo e($labelId); ?>"
        tabindex="-1"
        class="absolute z-10 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm"
      >
      <li
            :id="id + '-option-none'"
            role="option"
            class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-400 bg-neutral-50 rounded-lg"
            :aria-selected="value == nullValue || value === null || value === ''"
            @click="select(null)"
          >
            <div class="flex items-center">
              <span
                class="block ml-3 truncate"
                :class="(value == nullValue || value === null || value === '')
                    ? 'font-medium text-neutral-900 group-hover:text-white'
                    : 'font-normal text-neutral-700 group-hover:text-neutral-100'"
                x-text="placeholder"
              ></span>
            </div>
            <span
              class="absolute inset-y-0 right-0 items-center pr-4 text-indigo-600 group-hover:text-white"
              :class="(value == nullValue || value === null || value === '') ? 'flex' : 'hidden'"
              aria-hidden="true"
            >
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </span>
          </li>
        <template x-for="(opt, idx) in options" :key="opt.id">
          <li
            :id="id + '-option-' + idx"
            role="option"
            class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-400 bg-neutral-50 rounded-lg"
            :aria-selected="String(opt.id) === String(value)"
            @mousemove="activeIndex = idx"
            @click="select(opt.id)"
          >
            <div class="flex items-center">
              <span
                class="block ml-3 truncate"
                :class="String(opt.id) === String(value)
                    ? 'font-medium text-neutral-900 group-hover:text-white'
                    : 'font-normal text-neutral-700 group-hover:text-neutral-100'"
                x-text="opt.label"
              ></span>
            </div>

            <span
              class="absolute inset-y-0 right-0 items-center pr-4 text-indigo-600 group-hover:text-white"
              :class="String(opt.id) === String(value) ? 'flex' : 'hidden'"
              aria-hidden="true"
            >
              <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </span>
          </li>
        </template>

        <!--[if BLOCK]><![endif]--><?php if($wireModel): ?>
          <input type="hidden" name="<?php echo e($wireModel); ?>">
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
      </ul>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
  </div>
</div>

<?php if (! $__env->hasRenderedOnce('d5b68e74-d06d-456a-90d4-91b2afb85bad')): $__env->markAsRenderedOnce('d5b68e74-d06d-456a-90d4-91b2afb85bad'); ?>
  <style>[x-cloak]{display:none!important}</style>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/ui/select-dropdown.blade.php ENDPATH**/ ?>