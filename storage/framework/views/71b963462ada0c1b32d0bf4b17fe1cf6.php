<div class="sidemenu-title">
    <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
        <?php echo e($title ?? ''); ?>

    </h2>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-3 mt-4">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Order'),'mode' => 'gray','selected' => $orderName,'name' => 'orderId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Order')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($orderName),'name' => 'orderId']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchOrder','wire:model.live' => 'searchOrder']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchOrder','wire:model.live' => 'searchOrder']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'component\',\'order_type_id\',\'order\',\'---\',null)','selected' => '---' == $orderName,'wire:model' => 'component.order_type_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'component\',\'order_type_id\',\'order\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $orderName),'wire:model' => 'component.order_type_id.id']); ?>
                ---
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $attributes = $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $component = $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $_orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $_order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'component\',\'order_type_id\',\'order\',\''.e($_order->name).'\','.e($_order->id).')','selected' => $_order->id === $orderId,'wire:model' => 'component.order_type_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'component\',\'order_type_id\',\'order\',\''.e($_order->name).'\','.e($_order->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($_order->id === $orderId),'wire:model' => 'component.order_type_id.id']); ?>
                    <?php echo e($_order->name); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $attributes = $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $component = $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald384098dd1216f6f264fe579adbe3c2f)): ?>
<?php $attributes = $__attributesOriginald384098dd1216f6f264fe579adbe3c2f; ?>
<?php unset($__attributesOriginald384098dd1216f6f264fe579adbe3c2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald384098dd1216f6f264fe579adbe3c2f)): ?>
<?php $component = $__componentOriginald384098dd1216f6f264fe579adbe3c2f; ?>
<?php unset($__componentOriginald384098dd1216f6f264fe579adbe3c2f); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['component.order_type_id.id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    <div class="flex flex-col">
        <?php
            $selectedName = array_key_exists('rank_id',$this->component) ? $this->component['rank_id']['name'] : '---';
            $selectedId = array_key_exists('rank_id',$this->component) ? $this->component['rank_id']['id'] : -1;
        ?>
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Given rank'),'mode' => 'gray','selected' => $selectedName,'name' => 'rankId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Given rank')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedName),'name' => 'rankId']); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'component\',\'rank_id\',null,\'---\',null)','selected' => '---' ==  $selectedName,'wire:model' => 'component.rank_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'component\',\'rank_id\',null,\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' ==  $selectedName),'wire:model' => 'component.rank_id.id']); ?>
                ---
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $attributes = $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $component = $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $_ranks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $_rank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'component\',\'rank_id\',null,\''.e(trim($_rank->name)).'\','.e($_rank->id).')','selected' => $_rank->id === $selectedId,'wire:model' => 'component.rank_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'component\',\'rank_id\',null,\''.e(trim($_rank->name)).'\','.e($_rank->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($_rank->id === $selectedId),'wire:model' => 'component.rank_id.id']); ?>
                    <?php echo e($_rank->name); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $attributes = $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $component = $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald384098dd1216f6f264fe579adbe3c2f)): ?>
<?php $attributes = $__attributesOriginald384098dd1216f6f264fe579adbe3c2f; ?>
<?php unset($__attributesOriginald384098dd1216f6f264fe579adbe3c2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald384098dd1216f6f264fe579adbe3c2f)): ?>
<?php $component = $__componentOriginald384098dd1216f6f264fe579adbe3c2f; ?>
<?php unset($__componentOriginald384098dd1216f6f264fe579adbe3c2f); ?>
<?php endif; ?>
    </div>

    <div class="">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'component.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'component.name']); ?><?php echo e(__('Name')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'component.name','wire:model' => 'component.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'component.name','wire:model' => 'component.name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['component.name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'component.content']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'component.content']); ?><?php echo e(__('Content')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.textarea','data' => ['mode' => 'gray','name' => 'component.content','placeholder' => ''.e(__('')).'','wire:model.live.debounce.900ms' => 'component.content']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'component.content','placeholder' => ''.e(__('')).'','wire:model.live.debounce.900ms' => 'component.content']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $attributes = $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $component = $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['component.content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'component.dynamic_fields']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'component.dynamic_fields']); ?><?php echo e(__('Dynamic fields')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.textarea','data' => ['mode' => 'gray','name' => 'component.dynamic_fields','placeholder' => ''.e(__('')).'','wire:model' => 'component.dynamic_fields']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'component.dynamic_fields','placeholder' => ''.e(__('')).'','wire:model' => 'component.dynamic_fields']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $attributes = $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $component = $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
    </div>
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'component.title']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'component.title']); ?><?php echo e(__('Title')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.textarea','data' => ['mode' => 'gray','name' => 'component.title','placeholder' => ''.e(__('')).'','wire:model.live.debounce.900ms' => 'component.title']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'component.title','placeholder' => ''.e(__('')).'','wire:model.live.debounce.900ms' => 'component.title']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $attributes = $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $component = $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
    </div>
</div>

<div class="flex justify-between items-end w-full">
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
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/component-action.blade.php ENDPATH**/ ?>