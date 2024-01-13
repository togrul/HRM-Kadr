<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'title',
     'selected',
     'mode' => 'default',
     'name' => '',
     'hasCheckbox' => false
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'title',
     'selected',
     'mode' => 'default',
     'name' => '',
     'hasCheckbox' => false
]); ?>
<?php foreach (array_filter(([
     'title',
     'selected',
     'mode' => 'default',
     'name' => '',
     'hasCheckbox' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $extraClass = match($mode)
     {
          'default' => 'bg-white',
          'gray' => 'bg-gray-100'
     };
     $isError = $errors->has($name)?'bg-red-50':'';
?>

<div x-data="{open : false}" class="w-full"
> 
     <?php if($hasCheckbox): ?>
      <div class="flex items-center space-x-2 justify-between">
        <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['id' => 'listbox-label','for' => 'listbox-label']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'listbox-label','for' => 'listbox-label']); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
        <?php echo e($checkbox); ?>

      </div>
      <?php else: ?>
      <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['id' => 'listbox-label','for' => 'listbox-label']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'listbox-label','for' => 'listbox-label']); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
     <?php endif; ?>
     <div class="relative mt-1">
       <button type="button" 
               class="relative w-full py-2 pl-3 pr-10 text-left <?php echo e($extraClass); ?> rounded-lg shadow-sm cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo e($isError); ?>" 
               aria-haspopup="listbox" 
               aria-expanded="true" 
               aria-labelledby="listbox-label"
               @click="open = !open"
               @click.away="open = false"
               @keydown.escape.window="open = false"
          >
         <span class="flex items-center">
           <span class="block ml-3 font-normal text-gray-900 truncate" 
           >
             <?php echo e($selected); ?>

           </span>
         </span>
         <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
           <!-- Heroicon name: solid/selector -->
           <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
             <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
           </svg>
         </span>
       </button>

       <ul
          <?php echo e($attributes->merge(['class' => 'absolute z-10 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm'])); ?>

          tabindex="-1" 
          role="listbox" 
          aria-labelledby="listbox-label" 
          aria-activedescendant="listbox-option-3"
          x-show="open"
          x-transition:enter="transition ease-in duration-100"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in duration-100"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0"
          style="display: none;"
        >
               <?php echo e($slot); ?>

       </ul>
     </div>
   </div><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/select-list.blade.php ENDPATH**/ ?>