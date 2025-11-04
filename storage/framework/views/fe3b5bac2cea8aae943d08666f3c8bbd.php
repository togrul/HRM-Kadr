<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'model',
    'value',
    'label'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'model',
    'value',
    'label'
]); ?>
<?php foreach (array_filter(([
    'model',
    'value',
    'label'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<label class="inline-flex items-center bg-neutral-100 rounded shadow-sm py-2 px-2">
    <input type="radio" class="form-radio" name="<?php echo e($model); ?>" wire:model="<?php echo e($model); ?>" value="<?php echo e($value); ?>">
    <span class="ml-2 text-sm font-normal"><?php echo e(__($label)); ?></span>
</label>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/ui/radio.blade.php ENDPATH**/ ?>