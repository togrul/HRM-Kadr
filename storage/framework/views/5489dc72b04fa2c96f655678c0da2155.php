<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['title', 'structureId', 'hasParent' => false, 'total_sum' => 0, 'total_filled' => 0, 'total_vacant' => 0]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['title', 'structureId', 'hasParent' => false, 'total_sum' => 0, 'total_filled' => 0, 'total_vacant' => 0]); ?>
<?php foreach (array_filter((['title', 'structureId', 'hasParent' => false, 'total_sum' => 0, 'total_filled' => 0, 'total_vacant' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'rounded-lg px-4 py-3 flex flex-col space-y-2 shadow-sm bg-slate-300/20',
]); ?>">
    <div class="flex items-center justify-between px-4 py-2">
        <h1 class="text-lg font-medium !text-zinc-900/80 flex flex-col space-y-1 items-start">
            <?php echo $title; ?></h1>
        <div class="flex space-x-2 items-center">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-staff')): ?>
                <button wire:click="openSideMenu('edit-staff',<?php echo e($structureId); ?>)"
                    class="appearance-none w-8 h-8 flex justify-center items-center rounded-lg bg-white/80 transition-all duration-300 hover:bg-white/60">
                    <?php echo $__env->make('components.icons.edit-icon', [
                        'color' => 'text-zinc-500',
                        'hover' => 'text-zinc-600',
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete-staff')): ?>
                <button wire:click.prevent="setDeleteStaff(<?php echo e($structureId); ?>)"
                    class="appearance-none w-8 h-8 flex justify-center items-center rounded-lg bg-white/80 transition-all duration-300 hover:bg-white/60">
                    <?php echo $__env->make('components.icons.delete-icon', [
                        'color' => 'text-rose-400',
                        'hover' => 'text-rose-300',
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 divide-x divide-zinc-300/60' => $hasParent,
    ]); ?>">
        <div class="md:col-span-2 flex flex-col space-y-2">
            <?php echo e($slot); ?>

        </div>
        <!--[if BLOCK]><![endif]--><?php if($hasParent): ?>
            <div class="px-6 flex flex-col space-y-2">
                <div class="flex flex-col bg-white/90 rounded-lg shadow-sm px-3 py-2">
                    <span class="text-sm font-medium text-gray-500"><?php echo e(__('Total count')); ?></span>
                    <span class="text-blue-600 text-xl font-medium"><?php echo e($total_sum); ?></span>
                </div>
                <div class="flex flex-col bg-white/90 rounded-lg shadow-sm px-3 py-2">
                    <span class="text-sm font-medium text-gray-500"><?php echo e(__('Total filled')); ?></span>
                    <span class="text-rose-500 text-xl font-medium"><?php echo e($total_filled); ?></span>
                </div>
                <div class="flex flex-col bg-white/90 rounded-lg shadow-sm px-3 py-2">
                    <span class="text-sm font-medium text-gray-500"><?php echo e(__('Total vacant')); ?></span>
                    <span class="text-green-500 text-xl font-medium"><?php echo e($total_vacant); ?></span>
                </div>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/staff/root.blade.php ENDPATH**/ ?>