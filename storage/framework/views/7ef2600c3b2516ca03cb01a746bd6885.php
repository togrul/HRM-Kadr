<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['title' => '']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['title' => '']); ?>
<?php foreach (array_filter((['title' => '']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<button
    <?php echo e($attributes->merge([
        'class' => 'flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300',
        'type' => 'button',
        'title' => $title,
    ])); ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/action-button.blade.php ENDPATH**/ ?>