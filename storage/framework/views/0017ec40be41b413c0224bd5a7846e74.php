<div class="flex flex-col space-y-2 w-full">
    <div class="grid grid-cols-3 gap-3">
        <div class="flex flex-col">
            <?php
                $selectedName = array_key_exists('rank_id',$contracts) ? $contracts['rank_id']['name'] : '---';
                $selectedId = array_key_exists('rank_id',$contracts) ? $contracts['rank_id']['id'] : -1;
            ?>
            <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Rank'),'mode' => 'default','selected' => $selectedName,'name' => 'rankId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Rank')),'mode' => 'default','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedName),'name' => 'rankId']); ?>
                <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'contracts\',\'rank_id\',null,\'---\',null)','selected' => '---' ==  $selectedName,'wire:model' => 'contracts.rank_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'contracts\',\'rank_id\',null,\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' ==  $selectedName),'wire:model' => 'contracts.rank_id.id']); ?>
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
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->ranks->toArray(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $rank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'contracts\',\'rank_id\',null,\''.e($rank).'\','.e($key).')','selected' => $key === $selectedId,'wire:model' => 'contracts.rank_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'contracts\',\'rank_id\',null,\''.e($rank).'\','.e($key).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key === $selectedId),'wire:model' => 'contracts.rank_id.id']); ?>
                        <?php echo e($rank); ?>

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
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['contracts.rank_id.id'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'contracts.contract_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'contracts.contract_date']); ?><?php echo e(__('Contract date')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'default','name' => 'contracts.contract_date','format' => 'Y-MM-DD','wire:model.live' => 'contracts.contract_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'default','name' => 'contracts.contract_date','format' => 'Y-MM-DD','wire:model.live' => 'contracts.contract_date']); ?>
                 <?php $__env->slot('script', null, []); ?> 
                    $el.onchange = function () {
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('contracts.contract_date', $el.value);
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
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['contracts.contract_date'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'contracts.contract_refresh_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'contracts.contract_refresh_date']); ?><?php echo e(__('Contract refresh date')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'default','name' => 'contracts.contract_refresh_date','format' => 'Y-MM-DD','wire:model.live' => 'contracts.contract_refresh_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'default','name' => 'contracts.contract_refresh_date','format' => 'Y-MM-DD','wire:model.live' => 'contracts.contract_refresh_date']); ?>
                 <?php $__env->slot('script', null, []); ?> 
                    $el.onchange = function () {
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('contracts.contract_refresh_date', $el.value);
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
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['contracts.contract_refresh_date'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'contracts.contract_duration']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'contracts.contract_duration']); ?><?php echo e(__('Contract duration')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'default','type' => 'number','name' => 'contracts.contract_duration','wire:model' => 'contracts.contract_duration']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'default','type' => 'number','name' => 'contracts.contract_duration','wire:model' => 'contracts.contract_duration']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['contracts.contract_duration'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'contracts.contract_ends_at']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'contracts.contract_ends_at']); ?><?php echo e(__('Contract end date')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'default','name' => 'contracts.contract_ends_at','format' => 'Y-MM-DD','wire:model.live' => 'contracts.contract_ends_at']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'default','name' => 'contracts.contract_ends_at','format' => 'Y-MM-DD','wire:model.live' => 'contracts.contract_ends_at']); ?>
                 <?php $__env->slot('script', null, []); ?> 
                    $el.onchange = function () {
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('contracts.contract_ends_at', $el.value);
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
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['contracts.contract_ends_at'];
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
    <div class="flex justify-end">
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'black','wire:click' => 'addContract']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'black','wire:click' => 'addContract']); ?><?php echo e(__('Add')); ?> <?php echo $__env->renderComponent(); ?>
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
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <?php if (isset($component)) { $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => [__('Rank'),__('Duration'),__('Contract date'),'action']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([__('Rank'),__('Duration'),__('Contract date'),'action'])]); ?>
                    <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $personnelModelData->contracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dataContract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
                                <span class="text-sm bg-slate-100 rounded-md px-3 py-1 font-medium flex justify-center items-center text-slate-600"><?php echo e($dataContract->rank->name); ?></span>
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
                                <span class="text-sm font-medium flex items-center text-slate-900"><?php echo e($dataContract->contract_duration); ?> <?php echo e(__('month')); ?></span>
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
                                    <div class="flex space-x-1 items-center">
                                        <span class="text-sm font-medium text-gray-500">
                                            <?php echo e(__('Contract date')); ?>:
                                        </span>
                                        <span class="text-sm font-medium text-gray-700">
                                            <?php echo e($dataContract->contract_date->format('d.m.Y')); ?>

                                       </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Contract refresh date')); ?>:</span>
                                        <span class="text-sm font-medium text-teal-500">
                                            <?php echo e($dataContract->contract_refresh_date->format('d.m.Y')); ?>

                                       </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Contract end date')); ?>:</span>
                                        <span class="text-sm font-medium text-rose-500">
                                            <?php echo e($dataContract->contract_ends_at->format('d.m.Y')); ?>

                                       </span>
                                    </div>
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
                                <button
                                    onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                                    wire:click="forceDeleteContract(<?php echo e($dataContract->id); ?>)"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                >
                                    <?php echo $__env->make('components.icons.force-delete', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                </button>
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
                            <td colspan="4">
                                <div class="flex justify-center items-center py-4">
                                    <span class="font-medium"><?php echo e(__('No information added')); ?></span>
                                </div>
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

</div>

<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/informations/contracts.blade.php ENDPATH**/ ?>