<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['selected' => false]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['selected' => false]); ?>
<?php foreach (array_filter((['selected' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<li <?php echo e($attributes->merge(['class' => 'group relative py-2 pl-3 cursor-pointer select-none hover:bg-blue-400 pr-9 bg-gray-50 rounded-lg'])); ?>

     role="option"
     @click="open = false"
>

     <div class="flex items-center">
          <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
               'block ml-3 truncate',
               'font-medium text-gray-900 group-hover:text-white' => $selected,
               'font-normal text-gray-700 group-hover:text-gray-100' => !$selected
           ]); ?>">
                <?php echo e($slot); ?>

          </span>
     </div>

     <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
          'absolute inset-y-0 right-0  items-center pr-4 text-indigo-600 group-hover:text-white',
          'flex' => $selected,
          'hidden' => !$selected
     ]); ?>">
          <!-- Heroicon name: solid/check -->
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
     </span>
</li>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/select-list-item.blade.php ENDPATH**/ ?>