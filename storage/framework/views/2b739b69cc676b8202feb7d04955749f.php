<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            <?php echo $title ?? ''; ?>

        </h2>
    </div>

<div 
    class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">

        <div class="col-span-3 px-2 py-2 border-2 rounded-lg">
            <div class="flex flex-row items-center h-8">
                <div class="flex items-center">
                    <label class="label">
                        <input wire:model.live="selectAll" type="checkbox" class="label__checkbox"  />
                        <span class="label__text">
                             <span class="label__check">
                                  <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                              </span>
                        </span>
                    </label>
                </div>
                <div class="text-sm">
                    <label for="selectAll"
                           class="font-medium text-gray-500">
                         <?php echo e(__('Select all')); ?>

                    </label>
                </div>
            </div>
        </div>

        <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex flex-col space-y-1" wire:key="<?php echo e($permission->id); ?>">
            <?php
                $prefix = explode('-',$permission->name)[0];
            ?>
            <div class="px-2 py-1 border-2 border-gray-300 border-dashed rounded-lg">
                <div class="flex flex-row items-center h-8">
                    <div class="flex items-center">
                        <label class="label">
                            <input wire:model="permissionList" value="<?php echo e($permission->id); ?>"
                                   id="<?php echo e($permission->id); ?>"
                                   type="checkbox" class="label__checkbox"  />
                            <span class="label__text">
                                <span class="label__check">
                                    <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </span>
                            </span>
                        </label>
                    </div>
                    <div class="text-xs">
                        <label for="permission_<?php echo e($permission->id); ?>"
                               class="font-medium text-gray-700 flex flex-col">
                            <span> <?php echo e($permission->name); ?></span>
                            <span class="text-blue-500"><?php echo e(__($prefix.'_permission')); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
           
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

  <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('Save permission')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
</div><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/roles/set-permission.blade.php ENDPATH**/ ?>