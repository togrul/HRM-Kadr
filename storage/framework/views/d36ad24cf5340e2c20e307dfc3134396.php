<div class="flex flex-col space-y-2">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            <?php echo $title ?? ''; ?>

        </h2>
    </div>

<div
    class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-8 transition duration-500 ease-in-out transform bg-white"
    x-data="{ activeTab: 'sections' }"
>
    <div class="tabs w-full bg-gray-50 rounded-lg px-1 py-1 flex space-x-2 items-center">
        <button
            class="tab appearance-none uppercase text-sm px-3 py-1 flex justify-center items-center transition-all duration-300 text-gray-500"
            :class="{ 'active border-b-2 border-blue-500 text-gray-900': activeTab === 'sections' }"
            @click="activeTab = 'sections'">
            <?php echo e(__('Sections')); ?>

        </button>
        <button
            class="tab appearance-none uppercase text-sm px-3 py-1 flex justify-center items-center transition-all duration-300  text-gray-500"
            :class="{ 'active border-b-2 border-blue-500 text-gray-900': activeTab === 'structures' }"
            @click="activeTab = 'structures'">
            <?php echo e(__('Structures')); ?>

        </button>
    </div>

    <div x-show="activeTab == 'sections'"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="grid grid-cols-1 gap-2 sm:grid-cols-3">

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

        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $keyData => $permissionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex flex-col space-y-1" wire:key="key-<?php echo e($keyData); ?>">
                <h2 class="text-sm uppercase font-medium text-blue-600 underline"><?php echo e(__($keyData)); ?></h2>
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $permissionData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex flex-col space-y-1" wire:key="<?php echo e($permission['id']); ?>">
                        <div class="px-2 py-1 border-2 border-gray-300 border-dashed rounded-lg">
                            <div class="flex flex-row items-center h-8">
                                <div class="flex items-center">
                                    <label class="label">
                                        <input wire:model="permissionList" value="<?php echo e($permission['id']); ?>"
                                               id="<?php echo e($permission['id']); ?>"
                                               type="checkbox" class="label__checkbox"  />
                                        <span class="label__text">
                                            <span class="label__check">
                                                <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <div class="text-xs">
                                    <label for="permission_<?php echo e($permission['id']); ?>"
                                           class="font-medium text-gray-700 flex flex-col"
                                    >
                                        <span class="text-xs uppercase font-semibold"><?php echo e(__($permission['title'])); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <div x-show="activeTab == 'structures'"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="grid grid-cols-1 gap-2 sm:grid-cols-3"
    >
        <div class="col-span-3 px-2 py-2 border-2 rounded-lg">
            <div class="flex flex-row items-center h-8">
                <div class="flex items-center">
                    <label class="label">
                        <input wire:model.live="selectAllStructure" type="checkbox" class="label__checkbox"  />
                        <span class="label__text">
                             <span class="label__check">
                                  <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                              </span>
                        </span>
                    </label>
                </div>
                <div class="text-sm">
                    <label for="selectAllStructure"
                           class="font-medium text-gray-500">
                        <?php echo e(__('Select all')); ?>

                    </label>
                </div>
            </div>
        </div>

        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $structures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $structure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex flex-col space-y-1" wire:key="<?php echo e($structure->id); ?>_<?php echo e($structure->shortname); ?>">
                <div class="px-2 py-1 border-2 border-gray-300 border-dashed rounded-lg">
                    <div class="flex flex-row items-center h-8">
                        <div class="flex items-center">
                            <label class="label">
                                <input wire:model="permissionStructureList"
                                       value="<?php echo e((int) $structure->id); ?>"
                                       wire:change="updatePermissionStructureList(<?php echo e($structure->id); ?>)"
                                       id="permission_<?php echo e($structure->id); ?>_<?php echo e($structure->shortname); ?>"
                                       type="checkbox"
                                       class="label__checkbox"
                                />
                                <span class="label__text">
                                    <span class="label__check">
                                        <svg class="w-6 h-6 icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </span>
                                </span>
                            </label>
                        </div>
                        <div class="text-sm">
                            <label for="permission_<?php echo e($structure->code); ?>_<?php echo e($structure->id); ?>"
                                   class="font-medium text-gray-700 flex flex-col">
                                <span> <?php echo e($structure->name); ?></span>
                                <span class="text-blue-500 text-xs"><?php echo e($structure->shortname); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

    </div>
</div>

  <?php if (isset($component)) { $__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('Save permission')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01)): ?>
<?php $attributes = $__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01; ?>
<?php unset($__attributesOriginaldc57d0f5eb34c46effd5ce9af16bca01); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01)): ?>
<?php $component = $__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01; ?>
<?php unset($__componentOriginaldc57d0f5eb34c46effd5ce9af16bca01); ?>
<?php endif; ?>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/roles/set-permission.blade.php ENDPATH**/ ?>