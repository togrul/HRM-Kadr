<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false,
    'width' => '24px',
    'height' => '24px'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false,
    'width' => '24px',
    'height' => '24px'
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false,
    'width' => '24px',
    'height' => '24px'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<svg <?php echo $attributes->merge(['class' => "$size $color transition-all duration-300 hover:{$hover}"]); ?>

     xmlns="http://www.w3.org/2000/svg"
     xmlns:xlink="http://www.w3.org/1999/xlink"
     width="<?php echo e($width); ?>"
     height="<?php echo e($height); ?>"
     viewBox="0 0 24 24"
     <?php if($animated): ?>
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
    <?php endif; ?>
>
    <?php echo e($slot); ?>

</svg>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/icons/root.blade.php ENDPATH**/ ?>