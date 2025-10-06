<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
  'label' => '',
  'options' => [],          // accepts [['id'=>1,'label'=>'...'], ...] or ['1'=>'...', ...]
  'placeholder' => '---',
  'mode' => 'default',      // 'default' | 'gray'
  'disabled' => false,
  'hasCheckbox' => false,   // if true, renders label + named slot "checkbox"
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
  'label' => '',
  'options' => [],          // accepts [['id'=>1,'label'=>'...'], ...] or ['1'=>'...', ...]
  'placeholder' => '---',
  'mode' => 'default',      // 'default' | 'gray'
  'disabled' => false,
  'hasCheckbox' => false,   // if true, renders label + named slot "checkbox"
]); ?>
<?php foreach (array_filter(([
  'label' => '',
  'options' => [],          // accepts [['id'=>1,'label'=>'...'], ...] or ['1'=>'...', ...]
  'placeholder' => '---',
  'mode' => 'default',      // 'default' | 'gray'
  'disabled' => false,
  'hasCheckbox' => false,   // if true, renders label + named slot "checkbox"
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

  // Resolve bound model key from wire:model / defer / lazy
  $wireModel = $attributes->wire('model')->value();

  // Normalize options: support keyed arrays or array-of-arrays/objects
  $normalized = collect($options)->map(function ($opt, $key) {
      if (is_array($opt) || is_object($opt)) {
          return [
              'id'    => data_get($opt, 'id', $key),
              'label' => data_get($opt, 'label', value(function () use ($opt) {
                  // best effort: first scalar field
                  foreach ((array)$opt as $v) if (is_scalar($v)) return (string)$v;
                  return '';
              })),
          ];
      }
      // associative map: ['1' => 'Label']
      return ['id' => $key, 'label' => $opt];
  })->values();

  $bg = match($mode) {
    'default' => 'bg-white',
    'gray'    => 'bg-gray-100',
    default   => 'bg-white',
  };

  $hasError = $wireModel ? $errors->has($wireModel) : false;
  $id = 'ui-select-'.Str::uuid();
?>

<div
  x-data="uiSelectDropdown({
      value: <?php if ((object) ($wireModel) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel->value()); ?>')<?php echo e($wireModel->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel); ?>')<?php endif; ?>,
      options: <?php echo \Illuminate\Support\Js::from($normalized)->toHtml() ?>,
      placeholder: <?php echo \Illuminate\Support\Js::from($placeholder)->toHtml() ?>,
      disabled: <?php echo \Illuminate\Support\Js::from($disabled)->toHtml() ?>,
      id: <?php echo \Illuminate\Support\Js::from($id)->toHtml() ?>,
  })"
  <?php echo e($attributes->except(['wire:model','wire:model.defer','wire:model.lazy'])->class('w-full')); ?>

>
  <!--[if BLOCK]><![endif]--><?php if($hasCheckbox): ?>
    <div class="flex items-center justify-between space-x-2">
      <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => $id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($id)]); ?><?php echo e($label); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => $id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($id)]); ?><?php echo e($label); ?> <?php echo $__env->renderComponent(); ?>
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
      :id="id + '-button'"
      class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo e($bg); ?> <?php echo e($disabled ? 'opacity-60 cursor-not-allowed' : ''); ?> <?php echo e($hasError ? 'bg-rose-50' : ''); ?>"
      :aria-expanded="open"
      :aria-controls="id + '-listbox'"
      :disabled="disabled"
      @click="toggle()"
      @keydown.arrow-down.prevent="openAndMove(1)"
      @keydown.arrow-up.prevent="openAndMove(-1)"
      @keydown.enter.prevent="commitActive()"
      @keydown.space.prevent="toggle()"
      @keydown.escape.stop="open=false"
    >
      <span class="flex items-center">
        <span class="block ml-3 font-normal text-gray-900 truncate" x-text="selectedLabel()"><?php echo e($placeholder); ?></span>
      </span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
        <!-- Heroicon: selector -->
        <svg class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </span>
    </button>

    <!--[if BLOCK]><![endif]--><?php if (! ($disabled)): ?>
      <ul
        x-ref="listbox"
        x-show="open"
        x-transition.opacity.duration.100ms
        x-cloak
        @click.outside="open=false"
        :id="id + '-listbox'"
        role="listbox"
        :aria-labelledby="id + '-label'"
        tabindex="-1"
        class="absolute z-10 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm"
      >
        <template x-for="(opt, idx) in options" :key="opt.id">
          <li
            :id="id + '-option-' + idx"
            role="option"
            class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-400 bg-gray-50 rounded-lg"
            :aria-selected="String(opt.id) === String(value)"
            @mousemove="activeIndex = idx"
            @click="select(opt.id)"
          >
            <div class="flex items-center">
              <span
                class="block ml-3 truncate"
                :class="String(opt.id) === String(value)
                    ? 'font-medium text-gray-900 group-hover:text-white'
                    : 'font-normal text-gray-700 group-hover:text-gray-100'"
                x-text="opt.label"
              ></span>
            </div>

            <span
              class="absolute inset-y-0 right-0 items-center pr-4 text-indigo-600 group-hover:text-white"
              :class="String(opt.id) === String(value) ? 'flex' : 'hidden'"
              aria-hidden="true"
            >
              <!-- Heroicon: check -->
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

<?php $__env->startPush('js'); ?>


<?php if (! $__env->hasRenderedOnce('ee6f3fda-1c17-4d1c-a1a7-c1e4688cce40')): $__env->markAsRenderedOnce('ee6f3fda-1c17-4d1c-a1a7-c1e4688cce40'); ?>
  <style>[x-cloak]{display:none!important}</style>
  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('uiSelectDropdown', ({ value, options, placeholder, disabled, id }) => ({
        id, open: false, value, options, placeholder, disabled, activeIndex: -1,

        toggle(){ if(this.disabled) return; this.open = !this.open; if(this.open) this.setActiveToCurrent(); this.$nextTick(() => this.open && this.$refs.listbox?.focus()); },
        openAndMove(step){ if(this.disabled) return; this.open = true; this.$nextTick(() => this.$refs.listbox?.focus()); this.move(step); },

        move(step){
          if (!this.options.length) return;
          if (this.activeIndex === -1) this.setActiveToCurrent();
          this.activeIndex = (this.activeIndex + step + this.options.length) % this.options.length;
          this.scrollActiveIntoView();
        },

        commitActive(){ if (this.activeIndex >= 0) this.select(this.options[this.activeIndex].id); },

        setActiveToCurrent(){
          const idx = this.options.findIndex(o => String(o.id) === String(this.value));
          this.activeIndex = idx >= 0 ? idx : 0;
        },

        select(id){ this.value = id; this.open = false; },

        selectedLabel(){
          const found = this.options.find(o => String(o.id) === String(this.value));
          return found ? found.label : this.placeholder;
        },

        scrollActiveIntoView(){
          const el = document.getElementById(this.id + '-option-' + this.activeIndex);
          el && el.scrollIntoView({ block: 'nearest' });
        }
      }));
    });
  </script>
<?php endif; ?>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/ui/select.blade.php ENDPATH**/ ?>