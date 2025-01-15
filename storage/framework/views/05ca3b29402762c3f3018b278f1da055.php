<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Education place'),'mode' => 'gray','selected' => $institutionName,'name' => 'educational_institution_id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Education place')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($institutionName),'name' => 'educational_institution_id']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchInstitution','wire:model.live' => 'searchInstitution']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchInstitution','wire:model.live' => 'searchInstitution']); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'education\',\'educational_institution_id\',\'institution\',\'---\',null)','selected' => '---' == $institutionName,'wire:model' => 'education.educational_institution_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'education\',\'educational_institution_id\',\'institution\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $institutionName),'wire:model' => 'education.educational_institution_id.id']); ?>
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
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'education\',\'educational_institution_id\',\'institution\',\''.e($institution->name).'\','.e($institution->id).')','selected' => $institution->id === $institutionId,'wire:model' => 'education.educational_institution_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'education\',\'educational_institution_id\',\'institution\',\''.e($institution->name).'\','.e($institution->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($institution->id === $institutionId),'wire:model' => 'education.educational_institution_id.id']); ?>
                <?php echo e($institution->name); ?>

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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.educational_institution_id.id'];
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
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Education form'),'mode' => 'gray','selected' => $educationFormName,'name' => 'educationFormId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Education form')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($educationFormName),'name' => 'educationFormId']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchEducationForm','wire:model.live' => 'searchEducationForm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchEducationForm','wire:model.live' => 'searchEducationForm']); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'education\',\'education_form_id\',\'educationForm\',\'---\',null)','selected' => '---' == $educationFormName,'wire:model' => 'education.education_form_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'education\',\'education_form_id\',\'educationForm\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $educationFormName),'wire:model' => 'education.education_form_id.id']); ?>
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
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $education_forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'education\',\'education_form_id\',\'educationForm\',\''.e($ef->name).'\','.e($ef->id).')','selected' => $ef->id === $educationFormId,'wire:model' => 'education.education_form_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'education\',\'education_form_id\',\'educationForm\',\''.e($ef->name).'\','.e($ef->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ef->id === $educationFormId),'wire:model' => 'education.education_form_id.id']); ?>
                <?php echo e($ef->name); ?>

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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.education_form_id.id'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.education_language']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.education_language']); ?><?php echo e(__('Education language')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'education.education_language','wire:model' => 'education.education_language']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.education_language','wire:model' => 'education.education_language']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.education_language'];
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
<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.specialty']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.specialty']); ?><?php echo e(__('Specialty')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'education.specialty','wire:model' => 'education.specialty']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.specialty','wire:model' => 'education.specialty']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.specialty'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.admission_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.admission_year']); ?><?php echo e(__('Admission year')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'education.admission_year','format' => 'Y-MM-DD','wire:model.live' => 'education.admission_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.admission_year','format' => 'Y-MM-DD','wire:model.live' => 'education.admission_year']); ?>
             <?php $__env->slot('script', null, []); ?> 
              $el.onchange = function () {
              window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('education.admission_year', $el.value);
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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.admission_year'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.graduated_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.graduated_year']); ?><?php echo e(__('Graduated year')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'education.graduated_year','format' => 'Y-MM-DD','wire:model.live' => 'education.graduated_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.graduated_year','format' => 'Y-MM-DD','wire:model.live' => 'education.graduated_year']); ?>
             <?php $__env->slot('script', null, []); ?> 
              $el.onchange = function () {
              window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('education.graduated_year', $el.value);
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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.graduated_year'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.profession_by_document']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.profession_by_document']); ?><?php echo e(__('Profession')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'education.profession_by_document','wire:model' => 'education.profession_by_document']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.profession_by_document','wire:model' => 'education.profession_by_document']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.profession_by_document'];
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
<div class="grid grid-cols-3 gap-2">
    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.diplom_serie']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.diplom_serie']); ?><?php echo e(__('Diplom serie')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'education.diplom_serie','wire:model' => 'education.diplom_serie']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.diplom_serie','wire:model' => 'education.diplom_serie']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.diplom_serie'];
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
        <div class="flex flex-col col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.diplom_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.diplom_no']); ?><?php echo e(__('Diplom no')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['type' => 'number','mode' => 'gray','name' => 'education.diplom_no','wire:model' => 'education.diplom_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','mode' => 'gray','name' => 'education.diplom_no','wire:model' => 'education.diplom_no']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.diplom_no'];
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

    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.diplom_given_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.diplom_given_date']); ?><?php echo e(__('Diplom given date')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'education.diplom_given_date','format' => 'Y-MM-DD','wire:model.live' => 'education.diplom_given_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'education.diplom_given_date','format' => 'Y-MM-DD','wire:model.live' => 'education.diplom_given_date']); ?>
             <?php $__env->slot('script', null, []); ?> 
              $el.onchange = function () {
              window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('education.diplom_given_date', $el.value);
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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['education.diplom_given_date'];
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

    <div class="flex space-x-2 items-start">
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.coefficient']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.coefficient']); ?><?php echo e(__('Coefficient')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','type' => 'number','name' => 'education.coefficient','wire:model.live' => 'education.coefficient']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','type' => 'number','name' => 'education.coefficient','wire:model.live' => 'education.coefficient']); ?> <?php echo $__env->renderComponent(); ?>
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
        <div class="flex flex-col items-start space-y-1">
            <div class="flex flex-row-reverse">
                <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.calculate_as_seniority']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.calculate_as_seniority']); ?><?php echo e(__('Calculate as seniority')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal74b62b190a03153f11871f645315f4de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b62b190a03153f11871f645315f4de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.checkbox','data' => ['name' => 'education.calculate_as_seniority','model' => 'education.calculate_as_seniority']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'education.calculate_as_seniority','model' => 'education.calculate_as_seniority']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $attributes = $__attributesOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__attributesOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $component = $__componentOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__componentOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
            </div>
            <div class="flex flex-row-reverse">
                <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'education.is_military']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'education.is_military']); ?><?php echo e(__('Is military?')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal74b62b190a03153f11871f645315f4de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b62b190a03153f11871f645315f4de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.checkbox','data' => ['name' => 'education.is_military','model' => 'education.is_military']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'education.is_military','model' => 'education.is_military']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $attributes = $__attributesOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__attributesOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $component = $__componentOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__componentOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
            </div>
        </div>

    </div>


</div>
<!--[if BLOCK]><![endif]--><?php if(Arr::has($education,['admission_year']) && !empty($education['admission_year'])): ?>
<div class="my-2 flex justify-between items-center border border-gray-200 p-2 shadow-sm bg-gray-50 rounded-lg">
        <div class="flex space-x-2 items-center">
            <span class="font-medium text-gray-500"><?php echo e(__('Duration')); ?>:</span>
            <!--[if BLOCK]><![endif]--><?php if(! empty($calculatedDataEducation)): ?>
            <span class="font-medium text-gray-900">
                <?php echo e($calculatedDataEducation['diff']); ?> <?php echo e(__('month')); ?>

                (<?php echo e($calculatedDataEducation['year']); ?> <?php echo e(__('year')); ?>

                <?php echo e($calculatedDataEducation['month']); ?> <?php echo e(__('month')); ?> )
            </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        <!--[if BLOCK]><![endif]--><?php if(Arr::has($education, 'education') && $education['coefficient'] > 0): ?>
        <div class="flex space-x-2 items-center">
            <span class="font-medium text-gray-500"><?php echo e(__('Coefficient')); ?>:</span>
            <span class="font-medium text-teal-500"><?php echo e($education['coefficient']); ?></span>
        </div>
        <div class="flex space-x-2 items-center">
            <span class="font-medium text-gray-500"><?php echo e(__('Extra seniority')); ?>:</span>
            <!--[if BLOCK]><![endif]--><?php if(! empty($calculatedDataEducation)): ?>
            <span class="font-medium text-rose-500">
                <?php echo e($calculatedDataEducation['duration']); ?> <?php echo e(__('month')); ?>

                (<?php echo e($calculatedDataEducation['year_coefficient']); ?> <?php echo e(__('year')); ?>

                <?php echo e($calculatedDataEducation['month_coefficient']); ?> <?php echo e(__('month')); ?>)
            </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php endif; ?><!--[if ENDBLOCK]><![endif]-->
<hr>
<div class="flex justify-center items-center px-4 py-3 rounded-xl shadow-sm w-full bg-slate-100 space-x-2">
    <span class="text-lg"><?php echo e(__('Extra Education')); ?></span>
    <?php if (isset($component)) { $__componentOriginal74b62b190a03153f11871f645315f4de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b62b190a03153f11871f645315f4de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.checkbox','data' => ['name' => 'hasExtraEducation','model' => 'hasExtraEducation']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'hasExtraEducation','model' => 'hasExtraEducation']); ?><?php echo e(__('Has extra education?')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $attributes = $__attributesOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__attributesOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $component = $__componentOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__componentOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
</div>

<!--[if BLOCK]><![endif]--><?php if($hasExtraEducation): ?>
<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Education type'),'mode' => 'gray','selected' => $educationTypeName,'name' => 'educationTypeId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Education type')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($educationTypeName),'name' => 'educationTypeId']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchEducationType','wire:model.live' => 'searchEducationType']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchEducationType','wire:model.live' => 'searchEducationType']); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'education_type_id\',\'educationType\',\'---\',null)','selected' => '---' == $educationTypeName,'wire:model' => 'extra_education.education_type_id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'education_type_id\',\'educationType\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $educationTypeName),'wire:model' => 'extra_education.education_type_id']); ?>
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
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $education_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'education_type_id\',\'educationType\',\''.e($type->name).'\','.e($type->id).')','selected' => $type->id === $educationTypeId,'wire:model' => 'extra_education.education_type_id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'education_type_id\',\'educationType\',\''.e($type->name).'\','.e($type->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type->id === $educationTypeId),'wire:model' => 'extra_education.education_type_id']); ?>
                <?php echo e($type->name); ?>

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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.education_type_id.id'];
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
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Education place'),'mode' => 'gray','selected' => $extraInstitutionName,'name' => 'extraInstitutionId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Education place')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraInstitutionName),'name' => 'extraInstitutionId']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchExtraInstitution','wire:model.live' => 'searchExtraInstitution']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchExtraInstitution','wire:model.live' => 'searchExtraInstitution']); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'educational_institution_id\',\'extraInstitution\',\'---\',null)','selected' => '---' == $extraInstitutionName,'wire:model' => 'extra_education.educational_institution_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'educational_institution_id\',\'extraInstitution\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $extraInstitutionName),'wire:model' => 'extra_education.educational_institution_id.id']); ?>
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
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $institutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'educational_institution_id\',\'extraInstitution\',\''.e($institution->name).'\','.e($institution->id).')','selected' => $institution->id === $extraInstitutionId,'wire:model' => 'extra_education.educational_institution_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'educational_institution_id\',\'extraInstitution\',\''.e($institution->name).'\','.e($institution->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($institution->id === $extraInstitutionId),'wire:model' => 'extra_education.educational_institution_id.id']); ?>
                <?php echo e($institution->name); ?>

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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.educational_institution_id.id'];
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
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Education form'),'mode' => 'gray','selected' => $extraEducationFormName,'name' => 'extraEducationFormId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Education form')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEducationFormName),'name' => 'extraEducationFormId']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchExtraEducationForm','wire:model.live' => 'searchExtraEducationForm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchExtraEducationForm','wire:model.live' => 'searchExtraEducationForm']); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'education_form_id\',\'extraEducationForm\',\'---\',null)','selected' => '---' == $extraEducationFormName,'wire:model' => 'extra_education.education_form_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'education_form_id\',\'extraEducationForm\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $extraEducationFormName),'wire:model' => 'extra_education.education_form_id.id']); ?>
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
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $education_forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'education_form_id\',\'extraEducationForm\',\''.e($ef->name).'\','.e($ef->id).')','selected' => $ef->id === $extraEducationFormId,'wire:model' => 'extra_education.education_form_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'education_form_id\',\'extraEducationForm\',\''.e($ef->name).'\','.e($ef->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ef->id === $extraEducationFormId),'wire:model' => 'extra_education.education_form_id.id']); ?>
                <?php echo e($ef->name); ?>

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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.education_form_id.id'];
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

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.name']); ?><?php echo e(__('Name')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'extra_education.name','wire:model' => 'extra_education.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.name','wire:model' => 'extra_education.name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.name'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.shortname']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.shortname']); ?><?php echo e(__('Shortname')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'extra_education.shortname','wire:model' => 'extra_education.shortname']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.shortname','wire:model' => 'extra_education.shortname']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.shortname'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.education_language']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.education_language']); ?><?php echo e(__('Education language')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'extra_education.education_language','wire:model' => 'extra_education.education_language']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.education_language','wire:model' => 'extra_education.education_language']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.education_language'];
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

<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.education_program_name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.education_program_name']); ?><?php echo e(__('Program name')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'extra_education.education_program_name','wire:model' => 'extra_education.education_program_name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.education_program_name','wire:model' => 'extra_education.education_program_name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.education_program_name'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.admission_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.admission_year']); ?><?php echo e(__('Admission year')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'extra_education.admission_year','format' => 'Y-MM-DD','wire:model.live' => 'extra_education.admission_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.admission_year','format' => 'Y-MM-DD','wire:model.live' => 'extra_education.admission_year']); ?>
             <?php $__env->slot('script', null, []); ?> 
              $el.onchange = function () {
              window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('extra_education.admission_year', $el.value);
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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.admission_year'];
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.graduated_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.graduated_year']); ?><?php echo e(__('Graduated year')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'extra_education.graduated_year','format' => 'Y-MM-DD','wire:model.live' => 'extra_education.graduated_year']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.graduated_year','format' => 'Y-MM-DD','wire:model.live' => 'extra_education.graduated_year']); ?>
             <?php $__env->slot('script', null, []); ?> 
              $el.onchange = function () {
              window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('extra_education.graduated_year', $el.value);
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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.graduated_year'];
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
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','title' => __('Document type'),'mode' => 'gray','selected' => $educationDocumentTypeName,'name' => 'educationDocumentTypeId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Document type')),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($educationDocumentTypeName),'name' => 'educationDocumentTypeId']); ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchDocumentTyoe','wire:model.live' => 'searchDocumentTyoe']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => 'searchDocumentTyoe','wire:model.live' => 'searchDocumentTyoe']); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'education_document_type_id\',\'educationDocumentType\',\'---\',null)','selected' => '---' == $extraEducationFormName,'wire:model' => 'extra_education.education_document_type_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'education_document_type_id\',\'educationDocumentType\',\'---\',null)','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' == $extraEducationFormName),'wire:model' => 'extra_education.education_document_type_id.id']); ?>
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
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $document_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\'extra_education\',\'education_document_type_id\',\'educationDocumentType\',\''.e($dt->name).'\','.e($dt->id).')','selected' => $dt->id === $educationDocumentTypeId,'wire:model' => 'extra_education.education_document_type_id.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\'extra_education\',\'education_document_type_id\',\'educationDocumentType\',\''.e($dt->name).'\','.e($dt->id).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dt->id === $educationDocumentTypeId),'wire:model' => 'extra_education.education_document_type_id.id']); ?>
                <?php echo e($dt->name); ?>

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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.education_document_type_id.id'];
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
<div class="grid grid-cols-3 gap-2">
    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.diplom_serie']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.diplom_serie']); ?><?php echo e(__('Diplom serie')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'extra_education.diplom_serie','wire:model' => 'extra_education.diplom_serie']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.diplom_serie','wire:model' => 'extra_education.diplom_serie']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.diplom_serie'];
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
        <div class="flex flex-col col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.diplom_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.diplom_no']); ?><?php echo e(__('Diplom no')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['type' => 'number','mode' => 'gray','name' => 'extra_education.diplom_no','wire:model' => 'extra_education.diplom_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','mode' => 'gray','name' => 'extra_education.diplom_no','wire:model' => 'extra_education.diplom_no']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.diplom_no'];
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

    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.diplom_given_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.diplom_given_date']); ?><?php echo e(__('Diplom given date')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'extra_education.diplom_given_date','format' => 'Y-MM-DD','wire:model.live' => 'extra_education.diplom_given_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'extra_education.diplom_given_date','format' => 'Y-MM-DD','wire:model.live' => 'extra_education.diplom_given_date']); ?>
             <?php $__env->slot('script', null, []); ?> 
              $el.onchange = function () {
              window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('extra_education.diplom_given_date', $el.value);
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
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['extra_education.diplom_given_date'];
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


    <div class="flex space-x-2 items-start">
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.coefficient']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.coefficient']); ?><?php echo e(__('Coefficient')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','type' => 'number','name' => 'extra_education.coefficient','wire:model.live' => 'extra_education.coefficient']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','type' => 'number','name' => 'extra_education.coefficient','wire:model.live' => 'extra_education.coefficient']); ?> <?php echo $__env->renderComponent(); ?>
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

        <div class="flex flex-col items-start space-y-1">
            <div class="flex flex-row-reverse">
                <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.calculate_as_seniority']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.calculate_as_seniority']); ?><?php echo e(__('Calculate as seniority')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal74b62b190a03153f11871f645315f4de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b62b190a03153f11871f645315f4de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.checkbox','data' => ['name' => 'extra_education.calculate_as_seniority','model' => 'extra_education.calculate_as_seniority']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'extra_education.calculate_as_seniority','model' => 'extra_education.calculate_as_seniority']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $attributes = $__attributesOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__attributesOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $component = $__componentOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__componentOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
            </div>
            <div class="flex flex-row-reverse">
                <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'extra_education.is_military']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'extra_education.is_military']); ?><?php echo e(__('Is military?')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal74b62b190a03153f11871f645315f4de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b62b190a03153f11871f645315f4de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.checkbox','data' => ['name' => 'extra_education.is_military','model' => 'extra_education.is_military']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'extra_education.is_military','model' => 'extra_education.is_military']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $attributes = $__attributesOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__attributesOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $component = $__componentOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__componentOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="flex justify-end">
    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'black','wire:click' => 'addEducation']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'black','wire:click' => 'addEducation']); ?><?php echo e(__('Add')); ?> <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => [__('Education place'),__('Info'),__('Duration'),'action']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([__('Education place'),__('Info'),__('Duration'),'action'])]); ?>
            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $extra_education_list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $eeModel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Name')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['name']); ?> (<?php echo e($eeModel['shortname']); ?>)
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Type')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['education_type_id']['name']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Form')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['education_form_id']['name']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Language')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['education_language']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Category')); ?>:</span>
                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'text-sm font-medium',
                                    'text-emerald-500' => !$eeModel['is_military'],
                                    'text-rose-500' => $eeModel['is_military']
                            ]); ?>">
                                <?php echo e($eeModel['is_military'] ? __('Military') : __('Property')); ?>

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
                                <?php echo e(__('Program')); ?>:
                            </span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['education_program_name']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Document')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['education_document_type_id']['name']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Serial number')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['diplom_serie']); ?><?php echo e($eeModel['diplom_no']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Given date')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e(\Carbon\Carbon::parse($eeModel['diplom_given_date'])->format('d.m.Y')); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Admission year')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['admission_year']); ?>

                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium"><?php echo e(__('Graduated year')); ?>:</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e($eeModel['graduated_year']); ?>

                           </span>
                        </div>

                        <div class="flex space-x-1 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                <?php echo e(__('Duration')); ?>:
                            </span>
                            <span class="text-sm font-medium text-gray-800">
                                <?php echo e($calculatedDataExtraEducation['data'][$key]['duration']['year']); ?> <?php echo e(__('year')); ?>

                                <?php echo e($calculatedDataExtraEducation['data'][$key]['duration']['month']); ?> <?php echo e(__('month')); ?>

                                (<?php echo e($calculatedDataExtraEducation['data'][$key]['duration']['diff']); ?> <?php echo e(__('month')); ?>)
                            </span>
                        </div>
                        <!--[if BLOCK]><![endif]--><?php if(!empty($eeModel['coefficient'])): ?>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                <?php echo e(__('Coefficient')); ?>:
                            </span>
                            <span class="text-sm font-medium text-blue-500">
                                <?php echo e($eeModel['coefficient']); ?>

                            </span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                <?php echo e(__('Extra seniority')); ?>:
                            </span>
                            <span class="text-sm font-medium text-blue-500">
                                <?php echo e($calculatedDataExtraEducation['data'][$key]['coefficient']['year']); ?> <?php echo e(__('year')); ?>

                                <?php echo e($calculatedDataExtraEducation['data'][$key]['coefficient']['month']); ?> <?php echo e(__('month')); ?>

                                (<?php echo e($calculatedDataExtraEducation['data'][$key]['duration']['duration']); ?> <?php echo e(__('month')); ?>)
                            </span>
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
                     <button
                        onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteData(<?php echo e($key); ?>)"
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
                <td colspan="5">
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
        <!--[if BLOCK]><![endif]--><?php if(count($extra_education_list) > 0): ?>
        <div class="my-2 flex justify-between items-center border border-gray-300 p-2 shadow-sm bg-gray-50 rounded-lg">
            <div class="flex space-x-2 items-center">
                <span class="font-medium text-gray-500"><?php echo e(__('Total duration')); ?>:</span>
                <span class="font-medium text-gray-900">
                    <?php echo e($calculatedDataExtraEducation['total_duration']); ?> <?php echo e(__('month')); ?>

                    ( <?php echo e($calculatedDataExtraEducation['total_duration_diff']['year']); ?> <?php echo e(__('year')); ?>

                    <?php echo e($calculatedDataExtraEducation['total_duration_diff']['month']); ?> <?php echo e(__('month')); ?>)
                </span>
            </div>
            <div class="flex space-x-2 items-center">
                <span class="font-medium text-gray-500"><?php echo e(__('Extra seniority')); ?>:</span>
                <span class="font-medium text-rose-500">
                    <?php echo e($calculatedDataExtraEducation['extra_seniority']); ?> <?php echo e(__('month')); ?>

                    ( <?php echo e($calculatedDataExtraEducation['extra_seniority_full']['year']); ?> <?php echo e(__('year')); ?>

                    <?php echo e($calculatedDataExtraEducation['extra_seniority_full']['month']); ?> <?php echo e(__('month')); ?>)
                </span>
            </div>

        </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>


<?php endif; ?><!--[if ENDBLOCK]><![endif]-->
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/step3.blade.php ENDPATH**/ ?>