<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'name',
    'model',
    'value' => null,
    'hidden' => false,
    'checked' => false,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'name',
    'model',
    'value' => null,
    'hidden' => false,
    'checked' => false,
]); ?>
<?php foreach (array_filter(([
    'name',
    'model',
    'value' => null,
    'hidden' => false,
    'checked' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $extraClass = $hidden ? 'text-gray-400 line-through' : 'text-gray-700';
?>

<div class="flex items-center">
    <label class="relative inline-flex items-center cursor-pointer <?php echo e($hidden ? 'line-through opacity-60' : ''); ?>">
        
        <input
            wire:model.live="<?php echo e($model); ?>"
            <?php if($value): ?> value="<?php echo e($value); ?>" <?php endif; ?>
            name="<?php echo e($name); ?>"
            type="checkbox"
            <?php if($checked): ?> <?php if(true): echo 'checked'; endif; ?> <?php endif; ?>
            <?php echo e($hidden ? 'disabled' : ''); ?>

            class="peer w-5 h-5 mr-2 appearance-none rounded border border-neutral-300
                   bg-neutral-100 transition-colors duration-150
                   focus:outline-none focus:ring-2 focus:ring-green-500/40
                   checked:bg-green-500 checked:border-green-500
                   disabled:opacity-50"
        />

        
        <svg
            class="absolute left-[0.25rem] top-[0.25rem] w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-150 pointer-events-none"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            viewBox="0 0 20 20"
        >
            <polyline points="5 10.5 8.5 14 15 6" />
        </svg>

        
        <span class="text-sm font-medium <?php echo e($extraClass); ?>">
            <?php echo e($slot); ?>

        </span>
    </label>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/checkbox.blade.php ENDPATH**/ ?>