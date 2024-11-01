<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<svg <?php echo e($attributes->merge(['class' => "$size $color transition-all duration-300 hover:$hover"])); ?>

     xmlns="http://www.w3.org/2000/svg"
     xmlns:xlink="http://www.w3.org/1999/xlink"
     width="24px"
     height="24px"
     viewBox="0 0 24 24"
     <?php if($animated): ?>
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
     <?php endif; ?>
>
    <defs/>
    <?php echo e($slot); ?>

</svg>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/icons/root.blade.php ENDPATH**/ ?>