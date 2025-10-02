<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['hasParent', 'model']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['hasParent', 'model']); ?>
<?php foreach (array_filter((['hasParent', 'model']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'flex flex-col space-y-1 w-full',
    'bg-white/50 rounded-lg shadow-sm px-3 py-2' => $hasParent,
]); ?>">
    <!--[if BLOCK]><![endif]--><?php if($hasParent): ?>
        <div class="px-3 py-2 w-max">
            <p class="text-base"><?php echo e($model->position->name); ?></p>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-center w-full">
        <div class="flex flex-col space-y-2 px-3 py-2 bg-white rounded-lg shadow-sm">
            <p class="text-sm font-medium text-zinc-500">
                <?php echo e(__('Total')); ?></p>
            <p class="text-blue-600/90 font-medium"><?php echo e($model->total); ?></p>
        </div>
        <div <?php if($hasParent): ?> wire:click="openSideMenu('show-staff',<?php echo e($model->structure_id); ?>,<?php echo e($model->position_id); ?>)" <?php endif; ?>
            class="flex flex-col cursor-pointer space-y-2 px-3 py-2 bg-white rounded-lg shadow-sm">
            <p class="text-sm font-medium text-zinc-500">
                <?php echo e(__('Filled')); ?></p>
            <p class="text-rose-600/90 font-medium"><?php echo e($model->filled); ?></p>
        </div>
        <div class="flex flex-col space-y-2 px-3 py-2 bg-white rounded-lg shadow-sm">
            <p class="text-sm font-medium text-zinc-500">
                <?php echo e(__('Vacant')); ?></p>
            <p class="text-green-600/90 font-medium"><?php echo e($model->vacant); ?></p>
        </div>
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/staff/item.blade.php ENDPATH**/ ?>