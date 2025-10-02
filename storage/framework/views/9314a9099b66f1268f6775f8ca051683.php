<div class="flex justify-start items-center flex-wrap gap-2">
    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button wire:click.prevent="setPosition(<?php echo e($position->id); ?>)" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'appearance-none w-max text-sm font-medium bg-gray-50 border rounded-md px-3 py-1 transition-all duration-300 hover:shadow-sm hover:text-gray-900',
            'shadow-none text-teal-500' => $position->id == $selectedPosition,
            'shadow-md text-gray-600' => $position->id != $selectedPosition,
        ]); ?>">
            <?php echo e($position->name); ?>

        </button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

    <!--[if BLOCK]><![endif]--><?php if(!empty($selectedPosition)): ?>
        <button wire:click.prevent="resetFilter"
            class="appearance-none w-max text-sm font-medium bg-slate-100 text-rose-500 rounded-2xl px-3 py-1 transition-all duration-300 hover:bg-slate-200">
            <?php echo e(__('Reset')); ?>

        </button>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/partials/personnel/position-filters.blade.php ENDPATH**/ ?>