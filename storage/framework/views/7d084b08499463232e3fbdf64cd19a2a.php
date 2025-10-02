<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['title', 'textColor' => 'text-zinc-900']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['title', 'textColor' => 'text-zinc-900']); ?>
<?php foreach (array_filter((['title', 'textColor' => 'text-zinc-900']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="flex flex-col space-y-1">
    <span class="text-zinc-500 text-sm font-medium border-b border-dashed border-zinc-500"><?php echo e(__($title)); ?></span>
    <span class="<?php echo e($textColor); ?> text-sm font-medium"><?php echo e($slot); ?></span>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/table/cell-vertical.blade.php ENDPATH**/ ?>