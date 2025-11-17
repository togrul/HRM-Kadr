<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'mode' => 'default'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'mode' => 'default'
]); ?>
<?php foreach (array_filter(([
     'mode' => 'default'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<nav class="flex-col items-center justify-between text-sm sm:flex">
     <ul class="<?php echo \Illuminate\Support\Arr::toCssClasses([
          'flex py-[1px] px-[1px] border font-medium rounded-lg space-x-[1px]',
          'border-gray-200 bg-gray-100' => $mode == 'default',
          'border-slate-800 bg-slate-900' => $mode == 'dark'
     ]); ?>">
          <?php echo e($slot); ?>

     </ul>
</nav>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/filter/nav.blade.php ENDPATH**/ ?>