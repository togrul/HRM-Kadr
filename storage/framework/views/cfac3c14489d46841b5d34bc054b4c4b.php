<div class="sidemenu-title">
    <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
      <?php echo e($title ?? ''); ?>

    </h2>
</div>

<div class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white">
    <div class="grid grid-cols-4 gap-y-4 items-center">
        <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:click="selectStep(<?php echo e($key); ?>)" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'flex items-center relative flex-col space-y-2 transition-all duration-300 hover:text-green-500 before:content-0 before:absolute before:w-full before:h-[5px] before:z-0 before:top-[14px] before:transition-all before:duration-300',
                'before:bg-gray-200' => $step <= $key,
                'before:bg-green-100' => $step > $key
            ]); ?>">
                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'flex-none w-8 h-8 flex justify-center items-center rounded-full border-2 z-10 transition-all duration-300',
                    'border-gray-200 text-black bg-gray-200' =>  ($step != $key && $step < $key),
                    'border-green-100 text-green-500 bg-green-100' => $step > $key,
                    'border-blue-500 bg-blue-500 text-white' => $step == $key
                ]); ?>">
                <?php if($step <= $key): ?>
                <span class="text-sm"><?php echo e($key); ?></span>
                <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <?php endif; ?>
                </span>
                <span class="text-sm"> <?php echo e($st); ?></span>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
       
    </div>
    <hr class="py-2" />

    <?php if($step == 1): ?>
    <?php echo $__env->make('includes.step1', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 2): ?>
    <?php echo $__env->make('includes.step2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 3): ?>
    <?php echo $__env->make('includes.step3', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 4): ?>
    <?php echo $__env->make('includes.step4', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 5): ?>
    <?php echo $__env->make('includes.step5', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 6): ?>
    <?php echo $__env->make('includes.step6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 7): ?>
    <?php echo $__env->make('includes.step7', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php if($step == 8): ?>
    <?php echo $__env->make('includes.step8', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <div class="flex justify-between items-end w-full">
        <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('Save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
        <div class="flex items-center space-x-2">
            <?php if($step > 1): ?>
            <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'warning','wire:click.prevent' => 'previousStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'warning','wire:click.prevent' => 'previousStep']); ?><?php echo e(__('Previous')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
            <?php endif; ?>
            <?php if(array_key_last($steps) != $step): ?>
            <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'success','wire:click.prevent' => 'nextStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'success','wire:click.prevent' => 'nextStep']); ?><?php echo e(__('Next')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/personnel-action.blade.php ENDPATH**/ ?>