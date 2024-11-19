<div class="flex flex-col"
    x-data
    x-init="
        paginator = document.querySelector('span[aria-current=page]>span');
        if(paginator != null)
        {
            paginator.classList.add('bg-blue-50','text-blue-600')
        }
        Livewire.hook('message.processed', (message,component) => {
            const paginator = document.querySelector('span[aria-current=page]>span')
            if(
                ['gotoPage','previousPage','nextPage','setStatus','resetFilter'].includes(message.updateQueue[0].payload.method)
                || ['openSideMenu','closeSideMenu','userAdded'].includes(message.updateQueue[0].payload.event)
                || ['q'].includes(message.updateQueue[0].name)
            ){
                if(paginator != null)
                {
                    paginator.classList.add('bg-blue-50','text-blue-600')
                }
            }
        })
    "
>

<div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(1)','active' => $status === 1]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(1)','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 1)]); ?>
            <?php echo e(__('Active')); ?>

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
        <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(0)','active' => $status === 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(0)','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 0)]); ?>
            <?php echo e(__('De-active')); ?>

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
        <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(2)','active' => $status === 2]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(2)','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 2)]); ?>
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


    <div class="flex items-center justify-center space-x-2 action-section">
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['class' => 'space-x-2','mode' => 'gray','wire:click.prevent' => 'resetFilter']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'space-x-2','mode' => 'gray','wire:click.prevent' => 'resetFilter']); ?>
            <?php if (isset($component)) { $__componentOriginal4c586efbee58f7254ac269a2a9596e5d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4c586efbee58f7254ac269a2a9596e5d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.refresh-icon','data' => ['color' => 'text-gray-800','hover' => 'text-gray-900']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.refresh-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-gray-800','hover' => 'text-gray-900']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4c586efbee58f7254ac269a2a9596e5d)): ?>
<?php $attributes = $__attributesOriginal4c586efbee58f7254ac269a2a9596e5d; ?>
<?php unset($__attributesOriginal4c586efbee58f7254ac269a2a9596e5d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4c586efbee58f7254ac269a2a9596e5d)): ?>
<?php $component = $__componentOriginal4c586efbee58f7254ac269a2a9596e5d; ?>
<?php unset($__componentOriginal4c586efbee58f7254ac269a2a9596e5d); ?>
<?php endif; ?>
            <span><?php echo e(__('Reset filter')); ?></span>
         <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['class' => 'space-x-2','mode' => 'primary','wire:click.prevent' => 'openSideMenu(\'add-user\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'space-x-2','mode' => 'primary','wire:click.prevent' => 'openSideMenu(\'add-user\')']); ?>
            <?php if (isset($component)) { $__componentOriginal9caad75e5d9c86a86a797eb334f67ce3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9caad75e5d9c86a86a797eb334f67ce3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.add-user','data' => ['color' => 'text-white','hover' => 'text-gray-50']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.add-user'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-white','hover' => 'text-gray-50']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9caad75e5d9c86a86a797eb334f67ce3)): ?>
<?php $attributes = $__attributesOriginal9caad75e5d9c86a86a797eb334f67ce3; ?>
<?php unset($__attributesOriginal9caad75e5d9c86a86a797eb334f67ce3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9caad75e5d9c86a86a797eb334f67ce3)): ?>
<?php $component = $__componentOriginal9caad75e5d9c86a86a797eb334f67ce3; ?>
<?php unset($__componentOriginal9caad75e5d9c86a86a797eb334f67ce3); ?>
<?php endif; ?>
            <span><?php echo e(__('Add User')); ?></span>
         <?php echo $__env->renderComponent(); ?>
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

<div class="grid grid-cols-1 gap-4 my-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    <div>
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'q']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'q']); ?><?php echo e(__('User name or email')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['id' => 'q','name' => 'q','mode' => 'gray','wire:model.live' => 'q','autocomplete' => 'off']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'q','name' => 'q','mode' => 'gray','wire:model.live' => 'q','autocomplete' => 'off']); ?> <?php echo $__env->renderComponent(); ?>
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
</div>

<div class="flex flex-col space-y-2">
    <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
            <?php if (isset($component)) { $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => [__('User'),__('Role'),__('Email'),__('Active?'),'action','action']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([__('User'),__('Role'),__('Email'),__('Active?'),'action','action'])]); ?>
                <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $_users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
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
                            <span class="text-sm font-medium">
                                <?php echo e($user->name); ?>

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
                            <!--[if BLOCK]><![endif]--><?php if(count($user->roles) > 0): ?>
                            <span class="bg-blue-100 text-blue-500 rounded-lg px-2 py-1 text-sm font-normal lowercase whitespace-no-wrap">
                                <?php echo e($user->roles[0]->name); ?>

                           </span>
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
                        <span class="text-sm font-normal text-gray-700">
                            <?php echo e($user->email); ?>

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
                        <div class="flex items-center justify-start">
                            <?php if (isset($component)) { $__componentOriginal897ab225cb3270e8fcce57c9120068df = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal897ab225cb3270e8fcce57c9120068df = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.check-icon','data' => ['size' => 'w-8 h-8','color' => $user->is_active ? 'text-green-400' : 'text-gray-300','hover' => $user->is_active ? 'text-green-500' : 'text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.check-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-8 h-8','color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user->is_active ? 'text-green-400' : 'text-gray-300'),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user->is_active ? 'text-green-500' : 'text-gray-400')]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal897ab225cb3270e8fcce57c9120068df)): ?>
<?php $attributes = $__attributesOriginal897ab225cb3270e8fcce57c9120068df; ?>
<?php unset($__attributesOriginal897ab225cb3270e8fcce57c9120068df); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal897ab225cb3270e8fcce57c9120068df)): ?>
<?php $component = $__componentOriginal897ab225cb3270e8fcce57c9120068df; ?>
<?php unset($__componentOriginal897ab225cb3270e8fcce57c9120068df); ?>
<?php endif; ?>
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
                        <!--[if BLOCK]><![endif]--><?php if($status == 2): ?>
                        <div class="flex flex-col text-xs font-medium">
                            <div class="flex items-center space-x-1">
                                <span class="text-gray-500"><?php echo e(__('Deleted date')); ?>:</span>
                                <span class="text-black"><?php echo e(\Carbon\Carbon::parse($user->deleted_at)->format('d-m-Y H:i')); ?></span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-gray-500"><?php echo e(__('Deleted by')); ?>:</span>
                                <span class="text-black"><?php echo e($user->personDidDelete->name); ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                            
                                <a href="#" wire:click.prevent="openSideMenu('edit-user',<?php echo e($user); ?>)" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                    <?php if (isset($component)) { $__componentOriginal308d511ba9bedd167c92178534240350 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal308d511ba9bedd167c92178534240350 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.edit-icon','data' => ['color' => 'text-slate-400','hover' => 'text-slate-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.edit-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-slate-400','hover' => 'text-slate-500']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal308d511ba9bedd167c92178534240350)): ?>
<?php $attributes = $__attributesOriginal308d511ba9bedd167c92178534240350; ?>
<?php unset($__attributesOriginal308d511ba9bedd167c92178534240350); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal308d511ba9bedd167c92178534240350)): ?>
<?php $component = $__componentOriginal308d511ba9bedd167c92178534240350; ?>
<?php unset($__componentOriginal308d511ba9bedd167c92178534240350); ?>
<?php endif; ?>
                                </a>
                            
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
                        <!--[if BLOCK]><![endif]--><?php if($status == 2): ?>
                        <button
                            wire:click="restoreData(<?php echo e($user->id); ?>)"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                        >
                            <?php if (isset($component)) { $__componentOriginal3b29863ca6c763a42daeb7da6d628a8a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.recover','data' => ['color' => 'text-teal-500','hover' => 'text-teal-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.recover'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-teal-500','hover' => 'text-teal-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a)): ?>
<?php $attributes = $__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a; ?>
<?php unset($__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b29863ca6c763a42daeb7da6d628a8a)): ?>
<?php $component = $__componentOriginal3b29863ca6c763a42daeb7da6d628a8a; ?>
<?php unset($__componentOriginal3b29863ca6c763a42daeb7da6d628a8a); ?>
<?php endif; ?>
                        </button>
                        
                            <button
                                onclick="confirm('Are you sure you want to remove this user?') || event.stopImmediatePropagation()"
                                wire:click.prevent="forceDeleteData(<?php echo e($user->id); ?>)"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                <?php if (isset($component)) { $__componentOriginalc68e9de97adc0f3f617404bb2241d8ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.force-delete','data' => ['color' => 'text-rose-400','hover' => 'text-rose-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.force-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-400','hover' => 'text-rose-500']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad)): ?>
<?php $attributes = $__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad; ?>
<?php unset($__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc68e9de97adc0f3f617404bb2241d8ad)): ?>
<?php $component = $__componentOriginalc68e9de97adc0f3f617404bb2241d8ad; ?>
<?php unset($__componentOriginalc68e9de97adc0f3f617404bb2241d8ad); ?>
<?php endif; ?>
                            </button>
                        
                        <?php else: ?>
                            
                            <button
                                wire:click.prevent = "setDeleteUser(<?php echo e($user->id); ?>)"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-rose-50 hover:bg-red-100 hover:text-gray-700"
                            >
                                <?php if (isset($component)) { $__componentOriginal795db0355ab159c86fb4ade6f5b93d10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal795db0355ab159c86fb4ade6f5b93d10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.delete-icon','data' => ['color' => 'text-rose-500','hover' => 'text-rose-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.delete-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-500','hover' => 'text-rose-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal795db0355ab159c86fb4ade6f5b93d10)): ?>
<?php $attributes = $__attributesOriginal795db0355ab159c86fb4ade6f5b93d10; ?>
<?php unset($__attributesOriginal795db0355ab159c86fb4ade6f5b93d10); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal795db0355ab159c86fb4ade6f5b93d10)): ?>
<?php $component = $__componentOriginal795db0355ab159c86fb4ade6f5b93d10; ?>
<?php unset($__componentOriginal795db0355ab159c86fb4ade6f5b93d10); ?>
<?php endif; ?>
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
                    <tr>
                        <td colspan="9">
                          
                        </td>
                    </tr>
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

        <div>
            <?php echo e($_users->links()); ?>

        </div>
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
        <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'add-user'): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.users.add-user', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-2762846304-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'edit-user'): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.users.edit-user', ['userModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-2762846304-1', $__slots ?? [], get_defined_vars());

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
   

   <div class="">
        <!--[if BLOCK]><![endif]--><?php if(auth()->guard()->check()): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('services.users.delete-user');

$__html = app('livewire')->mount($__name, $__params, 'lw-2762846304-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
   </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/services/users/all-users.blade.php ENDPATH**/ ?>