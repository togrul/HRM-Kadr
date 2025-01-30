<div class="sidemenu-title">
    <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
      <?php echo e($title ?? ''); ?>

    </h2>
</div>

<div class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white">
    <div class="grid grid-cols-8 gap-y-2 items-start">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:click="selectStep(<?php echo e($key); ?>)" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'flex items-center relative flex-col space-y-2 transition-all duration-300 hover:text-green-500 before:content-0 before:rounded-xl before:absolute before:w-1/2 before:left-3/4 before:h-[3px] before:z-0 before:top-[22px] before:transition-all before:duration-300 last:before:w-0',
                'before:bg-gray-200' => $step <= $key,
                'before:bg-emerald-500' => $step > $key
            ]); ?>">
                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'flex-none w-12 h-12 flex justify-center items-center rounded-full z-10 transition-all duration-300 border-[6px] border-white',
                    'border-gray-200 text-black bg-gray-200' =>  ($step != $key && $step < $key),
                    'text-emerald-50 bg-emerald-500' => $step > $key,
                    'bg-blue-600 text-white' => $step == $key
                ]); ?>">
                    <!--[if BLOCK]><![endif]--><?php if($step <= $key): ?>
                        <span class="text-sm"><?php echo e($key); ?></span>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </span>
                <span class="text-sm"> <?php echo e($st); ?></span>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

    </div>
    <hr class="py-2" />

    <!--[if BLOCK]><![endif]--><?php if($step >= 1 && $step <= 8): ?>
        <?php echo $__env->make('includes.step' . $step, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <div class="flex justify-between items-end w-full">
        <!--[if BLOCK]><![endif]--><?php if(! auth()->user()->can('update-personnels') && isset($personnelModel)): ?>
            <div class="flex space-x-2 items-center">
                <?php if (isset($component)) { $__componentOriginal197957cb487bb6b611bd5c5b4499498e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal197957cb487bb6b611bd5c5b4499498e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.lock-icon','data' => ['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-7 h-7']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.lock-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-7 h-7']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal197957cb487bb6b611bd5c5b4499498e)): ?>
<?php $attributes = $__attributesOriginal197957cb487bb6b611bd5c5b4499498e; ?>
<?php unset($__attributesOriginal197957cb487bb6b611bd5c5b4499498e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal197957cb487bb6b611bd5c5b4499498e)): ?>
<?php $component = $__componentOriginal197957cb487bb6b611bd5c5b4499498e; ?>
<?php unset($__componentOriginal197957cb487bb6b611bd5c5b4499498e); ?>
<?php endif; ?>
                <span class="text-sm text-slate-500"><?php echo e(__('You have no permission to edit.')); ?></span>
            </div>
        <?php else: ?>
            <?php if (isset($component)) { $__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('Save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01)): ?>
<?php $attributes = $__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01; ?>
<?php unset($__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01)): ?>
<?php $component = $__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01; ?>
<?php unset($__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01); ?>
<?php endif; ?>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        <div class="flex items-center space-x-2">
            <!--[if BLOCK]><![endif]--><?php if($step > 1): ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'warning','wire:click.prevent' => 'previousStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'warning','wire:click.prevent' => 'previousStep']); ?><?php echo e(__('Previous')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <!--[if BLOCK]><![endif]--><?php if(array_key_last($steps) != $step): ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'success','wire:click.prevent' => 'nextStep']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'success','wire:click.prevent' => 'nextStep']); ?><?php echo e(__('Next')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/personnel-action.blade.php ENDPATH**/ ?>