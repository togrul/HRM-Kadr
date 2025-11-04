<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
  'label' => '',
  'placeholder' => '---',
  'mode' => 'default',
  'disabled' => false,
  'model' => [],  // [{id,label}]
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
  'label' => '',
  'placeholder' => '---',
  'mode' => 'default',
  'disabled' => false,
  'model' => [],  // [{id,label}]
]); ?>
<?php foreach (array_filter(([
  'label' => '',
  'placeholder' => '---',
  'mode' => 'default',
  'disabled' => false,
  'model' => [],  // [{id,label}]
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
  $wireModelDirective = $attributes->wire('model'); // 'filter.structure_id' vb.
  $wireModel = optional($wireModelDirective)->value();
  $uid = 'ui-select-'.Str::slug($wireModel ?? Str::uuid(), '_');
  $labelId = $uid.'-label';
  $bg = $mode === 'gray' ? 'bg-neutral-100' : 'bg-white';
?>

<div
  wire:key="select-<?php echo e($uid); ?>"
  x-data="{
    valueProxy: <?php if($wireModel): ?> <?php if ((object) ($wireModel) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel->value()); ?>')<?php echo e($wireModel->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($wireModel); ?>')<?php endif; ?>.live <?php else: ?> null <?php endif; ?>,
    cachedOptions: <?php echo \Illuminate\Support\Js::from($model)->toHtml() ?>,
    placeholder: <?php echo \Illuminate\Support\Js::from($placeholder)->toHtml() ?>,
    uid: <?php echo \Illuminate\Support\Js::from($uid)->toHtml() ?>,
    isOpen: false,
    currentValue: null,
    selectedCache: { id: null, label: '' },

    resolve(t){
      if (!t) return t;
      if (typeof t.get === 'function') return t.get();
      if (typeof t.value !== 'undefined') return t.value;
      return t;
    },
    toId(v){ return (v===null||v===undefined||v==='') ? null : String(v); },

    init(){
      this.syncValue();
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
      this.isOpen = false;
    },

    toggle(){ this.isOpen = !this.isOpen; },
  }"
  @click.window="if (!$el.contains($event.target)) isOpen = false"
  @keydown.escape.window="isOpen = false"
  <?php echo e($attributes->except(['wire:model','wire:model.defer','wire:model.lazy'])->class('w-full')); ?>

>
  <!--[if BLOCK]><![endif]--><?php if($label): ?>
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
      type="button" id="<?php echo e($uid); ?>-button"
      class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo e($bg); ?>"
      :aria-expanded="isOpen" aria-labelledby="<?php echo e($labelId); ?>"
      @click="toggle()"
    >
      <span class="flex items-center">
        <span class="block ml-3 font-normal text-neutral-900 truncate" x-text="selectedLabel()"><?php echo e($placeholder); ?></span>
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
      
      <?php echo e($slot); ?>


      
      <li class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-400 bg-neutral-50 rounded-lg"
          @click="select(null)">
        <div class="flex items-center">
          <span class="block ml-3 truncate"> <?php echo e($placeholder); ?> </span>
        </div>
      </li>

      <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li
          wire:key="<?php echo e($uid); ?>-<?php echo e($opt['id']); ?>"
          class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-400 bg-neutral-50 rounded-lg"
          @click="select('<?php echo e($opt['id']); ?>', '<?php echo e(e($opt['label'])); ?>')"
        >
          <div class="flex items-center">
            <span class="block ml-3 truncate"><?php echo e($opt['label']); ?></span>
          </div>
        </li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </ul>
  </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/ui/select-dropdown.blade.php ENDPATH**/ ?>