<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'size' => 'lg',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'size' => 'lg',
]); ?>
<?php foreach (array_filter(([
    'size' => 'lg',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<img src="<?php echo e(asset('assets/images/logo3.png')); ?>" alt="logo" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'h-40' => $size == 'lg',
    'h-24' => $size == 'sm',
    'h-20' => $size == 'xs',
]); ?>" />
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/application-logo.blade.php ENDPATH**/ ?>