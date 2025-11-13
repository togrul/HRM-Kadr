<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'valid' => false
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'valid' => false
]); ?>
<?php foreach (array_filter(([
    'valid' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'px-3 py-1 text-xs rounded-lg font-medium w-max max-w-[120px] flex justify-center items-center space-x-2 shadow-sm',
    'bg-emerald-50 text-emerald-500' => $valid,
    'bg-rose-50 text-rose-500' => ! $valid
]); ?>">
     <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
           'w-2 h-2 rounded-full shadow-sm flex',
           'bg-emerald-400' => $valid ,
           'bg-rose-400' => ! $valid ,
    ]); ?>">
     </span>
    <span class="uppercase"><?php echo e($valid ? __('Active') : __('De-active')); ?></span>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/status-badge.blade.php ENDPATH**/ ?>