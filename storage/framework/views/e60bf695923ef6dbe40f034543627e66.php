<div
    x-data="leavesIndex()"
    x-init="init()"
    class="flex flex-col"
>
    
       <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
       <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Leave type')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.leave_type_id','model' => $this->leaveTypes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Leave type')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.leave_type_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->leaveTypes)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.fullname']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.fullname']); ?><?php echo e(__('Fullname')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.fullname','wire:model' => 'filter.fullname']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.fullname','wire:model' => 'filter.fullname']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col w-full space-y-1">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.gender']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.gender']); ?><?php echo e(__('Gender')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex space-x-2">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = \App\Enums\GenderEnum::genderOptions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="inline-flex items-center w-full px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.gender" wire:model="filter.gender"
                               value="<?php echo e($value); ?>">
                        <span class="ml-2 text-sm font-normal"><?php echo e($label); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </div>
       <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.reason']); ?><?php echo e(__('Reason')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','type' => 'text','name' => 'filter.reason','wire:model' => 'filter.reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','type' => 'text','name' => 'filter.reason','wire:model' => 'filter.reason']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
      </div>

        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.appeal_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.appeal_date']); ?><?php echo e(__('Dates')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex items-center space-x-1">
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.starts_at','format' => 'Y-MM-DD','wire:model' => 'filter.starts_at']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.starts_at','format' => 'Y-MM-DD','wire:model' => 'filter.starts_at']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                        $el.onchange = function () {
                        window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.starts_at', $el.value);
                        }
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
                <span>-</span>
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.ends_at','format' => 'Y-MM-DD','wire:model' => 'filter.ends_at']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.ends_at','format' => 'Y-MM-DD','wire:model' => 'filter.ends_at']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                        $el.onchange = function () {
                        window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.ends_at', $el.value);
                        }
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
            </div>
        </div>
        <div class="flex items-end space-x-2">
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'primary','wire:click' => 'searchFilter','wire:loading.attr' => 'disabled','wire:target' => 'searchFilter']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'primary','wire:click' => 'searchFilter','wire:loading.attr' => 'disabled','wire:target' => 'searchFilter']); ?><?php echo e(__('Search')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'black','wire:click' => 'resetFilter','wire:loading.attr' => 'disabled','wire:target' => 'resetFilter']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'black','wire:click' => 'resetFilter','wire:loading.attr' => 'disabled','wire:target' => 'resetFilter']); ?><?php echo e(__('Reset')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
        </div>
    </div>
    

    
    <div class="flex flex-col px-6 py-4 space-y-4">
        
        <div class="flex items-center justify-between">
             <div class="px-2 py-2 bg-white filter rounded-xl">
                <?php if (isset($component)) { $__componentOriginal6307c0378087124f8a5ba7af51640019 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6307c0378087124f8a5ba7af51640019 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.nav','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'all\')','active' => $status === 'all']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'all\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'all')]); ?>
                        <?php echo e(__('All')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $_appeal_statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $_status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus('.e($_status->id).')','active' => $status === $_status->id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus('.e($_status->id).')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === $_status->id)]); ?>
                            <?php echo e($_status->name); ?>

                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'deleted\')','active' => $status === 'deleted']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'deleted\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'deleted')]); ?>
                        <?php echo e(__('Deleted')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6307c0378087124f8a5ba7af51640019)): ?>
<?php $attributes = $__attributesOriginal6307c0378087124f8a5ba7af51640019; ?>
<?php unset($__attributesOriginal6307c0378087124f8a5ba7af51640019); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6307c0378087124f8a5ba7af51640019)): ?>
<?php $component = $__componentOriginal6307c0378087124f8a5ba7af51640019; ?>
<?php unset($__componentOriginal6307c0378087124f8a5ba7af51640019); ?>
<?php endif; ?>
            </div>

            <div class="flex space-x-4">
                 
                 <button wire:click="openSideMenu('add-leave')"
                        class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-blue-50"
                        type="button"
                >
                        <?php echo $__env->make('components.icons.add-file', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                 </button>
                
                
                <button wire:click.prevent="exportExcel"
                        wire:loading.attr="disabled"
                        wire:target="exportExcel"
                        class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                        type="button"
                >
                        <?php if (isset($component)) { $__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.excel-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.excel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28)): ?>
<?php $attributes = $__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28; ?>
<?php unset($__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28)): ?>
<?php $component = $__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28; ?>
<?php unset($__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28); ?>
<?php endif; ?>
                </button>
                <button
                        class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-red-50"
                        type="button">
                        <?php echo $__env->make('components.icons.print-file', [
                                'color' => 'text-rose-500',
                                'hover' => 'text-rose-600',
                                'size' => 'w-8 h-8',
                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </button>
                
            </div>
        </div>
        
    </div>
    

    <div class="flex flex-col">
    
    <div class="relative min-h-[300px] overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <?php if (isset($component)) { $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => $this->getTableHeaders()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->getTableHeaders())]); ?>
                        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $permits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr wire:key="leave-row-<?php echo e($leave->id); ?>">
                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo e(($permits->currentpage() - 1) * $permits->perpage() + $key + 1); ?>

                                    </span>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-neutral-900">
                                            <?php echo e($leave->personnel->fullname_max); ?>

                                        </span>
                                        <span class="text-sm font-medium text-neutral-600/80">
                                            <?php echo e($leave->personnel->structure->name); ?>

                                        </span>
                                        <span class="text-sm font-medium text-emerald-600">
                                            <?php echo e($leave->personnel->position->name); ?>

                                        </span>
                                    </div>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                    <?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['statusId' => $leave->leave_type_id * 10,'label' => $leave->leaveType->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($leave->leave_type_id * 10),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($leave->leaveType->name)]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                    <div class="flex flex-col space-y-1">
                                      <span class="flex-wrap text-sm font-medium whitespace-normal"><?php echo e($leave->periodLabel); ?></span>
                                      <span class="text-sm font-medium text-neutral-700/80">(<?php echo e($leave->total_days); ?> <?php echo e(__('day')); ?>)</span>
                                    </div>
                                      <!--[if BLOCK]><![endif]--><?php if($leave->deleted_at): ?>
                                          <hr class="my-1" >
                                           <div class="flex items-center mt-2 space-x-1 text-xs font-medium">
                                                <span class="text-rose-500"><?php echo e(__('Deleted date')); ?>:</span>
                                                <span class="text-black"><?php echo e(\Carbon\Carbon::parse($leave->deleted_at)->format('d-m-Y H:i')); ?></span>
                                            </div>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                               <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                    <span class="text-sm font-medium text-gray-700 whitespace-normal flex w-[160px] bg-white rounded-xl shadow-lg px-3 py-2">
                                        <?php echo e($leave->reason); ?>

                                    </span>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center gap-2">
                                            <?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['statusId' => $leave->status_id,'label' => $leave->status->name,'type' => 'order','design' => 'modern']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($leave->status_id),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($leave->status->name),'type' => 'order','design' => 'modern']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
                                            <!--[if BLOCK]><![endif]--><?php if($leave?->latestLog?->comment): ?>
                                            <div class="relative top-1" x-data="{showComment: false}">
                                                <button @click="showComment = true" class="appearance-none">
                                                    <?php if (isset($component)) { $__componentOriginalca68a54e2ca7ed34ea211838b4b5640c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca68a54e2ca7ed34ea211838b4b5640c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.comment-icon','data' => ['color' => 'text-indigo-500','hover' => 'text-indigo-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.comment-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-indigo-500','hover' => 'text-indigo-700']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalca68a54e2ca7ed34ea211838b4b5640c)): ?>
<?php $attributes = $__attributesOriginalca68a54e2ca7ed34ea211838b4b5640c; ?>
<?php unset($__attributesOriginalca68a54e2ca7ed34ea211838b4b5640c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalca68a54e2ca7ed34ea211838b4b5640c)): ?>
<?php $component = $__componentOriginalca68a54e2ca7ed34ea211838b4b5640c; ?>
<?php unset($__componentOriginalca68a54e2ca7ed34ea211838b4b5640c); ?>
<?php endif; ?>
                                                </button>
                                                 <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                                    'absolute px-3 py-2 rounded-md shadow-2xl bg-white border border-neutral-200/80 w-[200px] z-10',
                                                    'bottom-10' => $loop->last,
                                                    'top-6' => !$loop->last,
                                                ]); ?>"
                                                    x-show="showComment"
                                                    x-transition.opacity
                                                    x-cloak
                                                    x-on:keydown.window.escape.prevent="showComment = false"
                                                    x-on:click.outside = "showComment = false"
                                                >
                                                    <span class="text-sm text-neutral-600">
                                                        <?php echo e($leave->latestLog->comment); ?>

                                                    </span>
                                                </div>
                                            </div>
                                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        </div>

                                        <!--[if BLOCK]><![endif]--><?php if($leave->status_id <> 10 && $leave->latestLog): ?>
                                        <div class="flex flex-col">
                                             <div class="flex items-center space-x-1 text-sm">
                                                <?php if (isset($component)) { $__componentOriginal1e7b99cf1692f258f5baf1d21a24d908 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1e7b99cf1692f258f5baf1d21a24d908 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.user-simple-icon','data' => ['size' => 'w-5 h-5','color' => 'text-neutral-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.user-simple-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-5 h-5','color' => 'text-neutral-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1e7b99cf1692f258f5baf1d21a24d908)): ?>
<?php $attributes = $__attributesOriginal1e7b99cf1692f258f5baf1d21a24d908; ?>
<?php unset($__attributesOriginal1e7b99cf1692f258f5baf1d21a24d908); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1e7b99cf1692f258f5baf1d21a24d908)): ?>
<?php $component = $__componentOriginal1e7b99cf1692f258f5baf1d21a24d908; ?>
<?php unset($__componentOriginal1e7b99cf1692f258f5baf1d21a24d908); ?>
<?php endif; ?>
                                                <span class="text-black"><?php echo e($leave->latestLog->changedBy->fullname); ?></span>
                                            </div>
                                            <div class="flex items-center space-x-1 text-sm">
                                                <?php if (isset($component)) { $__componentOriginal7634c7af2e3926ff7d7ebc53fb53cbb1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7634c7af2e3926ff7d7ebc53fb53cbb1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.clock-icon','data' => ['size' => 'w-5 h-5','color' => 'text-neutral-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.clock-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-5 h-5','color' => 'text-neutral-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7634c7af2e3926ff7d7ebc53fb53cbb1)): ?>
<?php $attributes = $__attributesOriginal7634c7af2e3926ff7d7ebc53fb53cbb1; ?>
<?php unset($__attributesOriginal7634c7af2e3926ff7d7ebc53fb53cbb1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7634c7af2e3926ff7d7ebc53fb53cbb1)): ?>
<?php $component = $__componentOriginal7634c7af2e3926ff7d7ebc53fb53cbb1; ?>
<?php unset($__componentOriginal7634c7af2e3926ff7d7ebc53fb53cbb1); ?>
<?php endif; ?>
                                                <span class="text-black">
                                                    <?php echo e(\Carbon\Carbon::parse($leave->latestLog->changed_at)->format('d.m.Y H:i')); ?>

                                                </span>
                                            </div>
                                        </div>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => ['standartWidth' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['standartWidth' => true]); ?>
                                    <div class="flex flex-col flex-wrap space-y-2 whitespace-normal">
                                        <!--[if BLOCK]><![endif]--><?php if($leave->document_path): ?>
                                        <a
                                            href="/<?php echo e($leave->document_path); ?>"
                                            target="_blank"
                                            class="flex flex-col gap-1 text-sm font-medium"
                                            style="word-break: break-word;"
                                        >
                                            <?php if (isset($component)) { $__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.files-icon','data' => ['color' => 'text-blue-500','hover' => 'text-sky-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.files-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-blue-500','hover' => 'text-sky-300']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9)): ?>
<?php $attributes = $__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9; ?>
<?php unset($__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9)): ?>
<?php $component = $__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9; ?>
<?php unset($__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9); ?>
<?php endif; ?>
                                            <span class="text-blue-500"><?php echo e(__('File')); ?></span>
                                        </a>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        <!--[if BLOCK]><![endif]--><?php if($leave->canBeApprovedBy(auth()->user())): ?>
                                        <div class="flex items-center space-x-2">
                                            <button
                                                wire:loading.attr="disabled"
                                                @click="$dispatch('comment:open', { action: 'APPROVED', leaveId: <?php echo \Illuminate\Support\Js::from($leave->id)->toHtml() ?> })"
                                            >
                                                <?php if (isset($component)) { $__componentOriginal897ab225cb3270e8fcce57c9120068df = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal897ab225cb3270e8fcce57c9120068df = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.check-icon','data' => ['color' => 'text-green-500','hover' => 'text-green-600','size' => 'w-8 h-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.check-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-green-500','hover' => 'text-green-600','size' => 'w-8 h-8']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal897ab225cb3270e8fcce57c9120068df)): ?>
<?php $attributes = $__attributesOriginal897ab225cb3270e8fcce57c9120068df; ?>
<?php unset($__attributesOriginal897ab225cb3270e8fcce57c9120068df); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal897ab225cb3270e8fcce57c9120068df)): ?>
<?php $component = $__componentOriginal897ab225cb3270e8fcce57c9120068df; ?>
<?php unset($__componentOriginal897ab225cb3270e8fcce57c9120068df); ?>
<?php endif; ?>
                                            </button>
                                            <button
                                                wire:loading.attr="disabled"
                                                @click="$dispatch('comment:open', { action: 'CANCELLED', leaveId: <?php echo \Illuminate\Support\Js::from($leave->id)->toHtml() ?> })"
                                            >
                                                <?php if (isset($component)) { $__componentOriginal2c0382cebda853a550091a02999e5afd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2c0382cebda853a550091a02999e5afd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.x-circle-icon','data' => ['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-8 h-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.x-circle-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-8 h-8']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2c0382cebda853a550091a02999e5afd)): ?>
<?php $attributes = $__attributesOriginal2c0382cebda853a550091a02999e5afd; ?>
<?php unset($__attributesOriginal2c0382cebda853a550091a02999e5afd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2c0382cebda853a550091a02999e5afd)): ?>
<?php $component = $__componentOriginal2c0382cebda853a550091a02999e5afd; ?>
<?php unset($__componentOriginal2c0382cebda853a550091a02999e5afd); ?>
<?php endif; ?>
                                            </button>
                                        </div>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => ['isButton' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isButton' => true]); ?>
                                    <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                                        
                                            <button
                                                wire:click="openSideMenu('edit-leave',<?php echo e($leave->id); ?>)"
                                                wire:loading.attr="disabled"
                                                wire:target="openSideMenu"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-700"
                                            >
                                                <?php echo $__env->make('components.icons.document-icon', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                            </button>
                                        
                                    <?php else: ?>
                                        <?php if(\Spatie\Permission\PermissionServiceProvider::bladeMethodWrapper('hasRole', 'Admin')): ?>
                                            <button wire:click="restoreData('<?php echo e($leave->id); ?>')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="restoreData('<?php echo e($leave->id); ?>')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-teal-50 hover:text-gray-700"
                                            >
                                                <?php echo $__env->make('components.icons.recover', [
                                                    'color' => 'text-teal-500',
                                                    'hover' => 'text-teal-600',
                                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => ['isButton' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isButton' => true]); ?>
                                    <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                                        
                                        <button
                                            wire:click="setDeleteLeave('<?php echo e($leave->id); ?>')"
                                            wire:loading.attr="disabled"
                                            wire:target="setDeleteLeave('<?php echo e($leave->id); ?>')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700"
                                        >
                                            <?php echo $__env->make('components.icons.delete-icon', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        </button>
                                        
                                    <?php else: ?>
                                        
                                        <button
                                            wire:confirm="<?php echo e(__('Are you sure you want to remove this data?')); ?>"
                                            wire:click="forceDeleteData('<?php echo e($leave->id); ?>')"
                                            wire:loading.attr="disabled"
                                            wire:target="forceDeleteData('<?php echo e($leave->id); ?>')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                        >
                                            <?php echo $__env->make('components.icons.force-delete', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                        </button>
                                        
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <?php if (isset($component)) { $__componentOriginal2d7ce9af332b48c30415ac50055c18a5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7ce9af332b48c30415ac50055c18a5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.empty','data' => ['rows' => count($this->getTableHeaders())]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(count($this->getTableHeaders()))]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2d7ce9af332b48c30415ac50055c18a5)): ?>
<?php $attributes = $__attributesOriginal2d7ce9af332b48c30415ac50055c18a5; ?>
<?php unset($__attributesOriginal2d7ce9af332b48c30415ac50055c18a5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2d7ce9af332b48c30415ac50055c18a5)): ?>
<?php $component = $__componentOriginal2d7ce9af332b48c30415ac50055c18a5; ?>
<?php unset($__componentOriginal2d7ce9af332b48c30415ac50055c18a5); ?>
<?php endif; ?>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656)): ?>
<?php $attributes = $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656; ?>
<?php unset($__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3ee30789824fd1cc17cb4ff8e03df656)): ?>
<?php $component = $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656; ?>
<?php unset($__componentOriginal3ee30789824fd1cc17cb4ff8e03df656); ?>
<?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="mt-2" x-ref="pager">
            <?php echo e($permits->links()); ?>

        </div>
    </div>

    <div class="" x-data>
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('confirmation.add-comment');

$__html = app('livewire')->mount($__name, $__params, 'lw-1595503810-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    </div>

    <?php if (isset($component)) { $__componentOriginal06466d70a5df71623dc2a561e77c49ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal06466d70a5df71623dc2a561e77c49ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.side-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('side-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'add-leave'): ?>
                 <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('outside.add-leave', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-1595503810-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        

        
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'edit-leave'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('outside.edit-leave', ['leaveModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-1595503810-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal06466d70a5df71623dc2a561e77c49ee)): ?>
<?php $attributes = $__attributesOriginal06466d70a5df71623dc2a561e77c49ee; ?>
<?php unset($__attributesOriginal06466d70a5df71623dc2a561e77c49ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal06466d70a5df71623dc2a561e77c49ee)): ?>
<?php $component = $__componentOriginal06466d70a5df71623dc2a561e77c49ee; ?>
<?php unset($__componentOriginal06466d70a5df71623dc2a561e77c49ee); ?>
<?php endif; ?>

    
        <div>
            <!--[if BLOCK]><![endif]--><?php if(auth()->guard()->check()): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('outside.delete-leave');

$__html = app('livewire')->mount($__name, $__params, 'lw-1595503810-3', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    

    <?php if (isset($component)) { $__componentOriginal2686ed4927c64f67d2844e9b73af898c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2686ed4927c64f67d2844e9b73af898c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.datepicker','data' => ['auto' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('datepicker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['auto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2686ed4927c64f67d2844e9b73af898c)): ?>
<?php $attributes = $__attributesOriginal2686ed4927c64f67d2844e9b73af898c; ?>
<?php unset($__attributesOriginal2686ed4927c64f67d2844e9b73af898c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2686ed4927c64f67d2844e9b73af898c)): ?>
<?php $component = $__componentOriginal2686ed4927c64f67d2844e9b73af898c; ?>
<?php unset($__componentOriginal2686ed4927c64f67d2844e9b73af898c); ?>
<?php endif; ?>
</div>

<?php $__env->startPush('js'); ?>
<script>
    function leavesIndex() {
        return {
            init() {
            // first paint
            this.highlightPager();

            // only react to specific Livewire actions/events
            Livewire.hook('message.processed', (m, c) => {
                    const method = m.updateQueue?.[0]?.payload?.method;
                    const event  = m.updateQueue?.[0]?.payload?.event;
                    const name   = m.updateQueue?.[0]?.name;

                    const methods = ['gotoPage','previousPage','nextPage','filterSelected'];
                    const events  = ['openSideMenu','closeSideMenu','leaveAdded','leaveUpdated','filterResetted','leaveWasDeleted', 'leaveApproved', 'leaveRejected'];
                    const names   = ['search'];

                    if (methods.includes(method) || events.includes(event) || names.includes(name)) {
                    this.highlightPager(true);
                }
            });
            },
                highlightPager(isUpdate = false) {
                const el = document.querySelector('span[aria-current=page]>span');
                if (!el) return;
                el.classList.remove('bg-blue-50','text-blue-600','bg-green-100','text-green-600');
                el.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
            }
        }
    }
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/outside/leaves.blade.php ENDPATH**/ ?>