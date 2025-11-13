<div class="flex flex-col space-y-8" x-data="{}">
    <div class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
            <?php echo e($title ?? ''); ?>

        </h2>
    </div>

    <div class="flex bg-slate-100 rounded-md py-1 px-1 w-max">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'flex appearance-none px-3 py-1 rounded-md justify-center items-center text-sm cursor-pointer transition-all duration-300 hover:bg-slate-50 text-slate-600',
                'bg-white shadow-sm text-slate-900' => $key === $currentStep
            ]); ?>"
                wire:click.prevent="setCurrentStep(<?php echo e($key); ?>)"
            >
                <?php echo e(__($step)); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <div class="flex w-full bg-slate-50 rounded-md px-2 py-3">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div x-show="$wire.currentStep === <?php echo e($key); ?>"
                 class="flex w-full"
            >
                <?php echo $__env->make("includes.informations.".\Illuminate\Support\Str::slug($step), \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/personnel/information.blade.php ENDPATH**/ ?>