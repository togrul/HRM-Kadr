<div class="flex flex-col space-y-8">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            <?php echo e($title ?? ''); ?>

        </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
        <div class="sm:col-span-2 flex items-end space-x-2">
            <div class="w-full">
                <div class="flex items-center space-x-2">
                    <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'types.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'types.name']); ?><?php echo e(__('Name')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
                    <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['types.name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>(* <?php echo e($message); ?> ) <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <!--[if ENDBLOCK]><![endif]-->
                </div>
                <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'types.name','wire:model' => 'types.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'types.name','wire:model' => 'types.name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>

            </div>
            <button class="rounded-lg shadow-sm bg-teal-500 text-slate-100 px-6 py-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-teal-600"
                    wire:click="addType"
            >
                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span class="uppercase"><?php echo e(__('Add')); ?></span>
            </button>
        </div>
    </div>

    <div class="flex flex-col space-y-2">
        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $_order_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center justify-between space-x-2 px-4 py-3 bg-slate-100 rounded-xl shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-slate-900">
                        <?php echo e($loop->iteration); ?>.
                    </span>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-slate-600">
                            <?php echo e($_type->name); ?>

                        </span>
                        <!--[if BLOCK]><![endif]--><?php if($selectedType == $_type->id): ?>
                            <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'default','name' => 'types.name','wire:model' => 'types.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'default','name' => 'types.name','wire:model' => 'types.name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
                            <button class="rounded-lg shadow-sm bg-green-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-green-200"
                                    wire:click="updateModel"
                            >
                                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5 text-green-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                </svg>
                            </button>
                            <button class="rounded-lg shadow-sm bg-rose-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-rose-200"
                                    wire:click="cancelUpdate"
                            >
                                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5 text-rose-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        <?php endif; ?> <!--[if ENDBLOCK]><![endif]-->
                    </div>

                </div>
                <div class="flex items-center space-x-3">
                    <button class="w-8 h-8 px-2 py-1 rounded-lg hover:bg-emerald-50 hover:shadow-sm font-medium text-sm flex justify-center items-center space-x-2 w-max"
                            wire:click="editType(<?php echo e($_type->id); ?>)"
                    >
                        <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6 text-emerald-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
                        </svg>
                    </button>
                    <button class="w-8 h-8 px-2 py-1 rounded-lg hover:bg-rose-50 hover:shadow-sm font-medium text-sm flex justify-center items-center space-x-2 w-max"
                            wire:click="removeType(<?php echo e($_type->id); ?>)"
                            wire:confirm="<?php echo e(__('Are you sure you want to delete?')); ?>"
                    >
                        <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6 text-rose-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="flex justify-start items-center px-4 py-3 font-medium bg-gray-100 rounded-lg text-gray-500 text-base">
                <span><?php echo e(__('No data exists.')); ?></span>
            </div>
        <?php endif; ?> <!--[if ENDBLOCK]><![endif]-->

    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/orders/templates/set-type.blade.php ENDPATH**/ ?>