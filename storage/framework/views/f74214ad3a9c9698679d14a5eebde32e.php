<div class="flex flex-col space-y-2 px-2 py-3">
    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'general','selectedService' => $selectedService,'title' => __('General')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'general','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('General'))]); ?>
        <?php if (isset($component)) { $__componentOriginal783b5678b4c316a95ec0b4a6cecafe91 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal783b5678b4c316a95ec0b4a6cecafe91 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.settings2-icon','data' => ['size' => 'w-6 h-6','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.settings2-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-6 h-6','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal783b5678b4c316a95ec0b4a6cecafe91)): ?>
<?php $attributes = $__attributesOriginal783b5678b4c316a95ec0b4a6cecafe91; ?>
<?php unset($__attributesOriginal783b5678b4c316a95ec0b4a6cecafe91); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal783b5678b4c316a95ec0b4a6cecafe91)): ?>
<?php $component = $__componentOriginal783b5678b4c316a95ec0b4a6cecafe91; ?>
<?php unset($__componentOriginal783b5678b4c316a95ec0b4a6cecafe91); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'menus','selectedService' => $selectedService,'title' => __('Menus')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'menus','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Menus'))]); ?>
        <?php if (isset($component)) { $__componentOriginalf893dcd45b7e3cdc4c978907b5a8e5e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf893dcd45b7e3cdc4c978907b5a8e5e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.menu-icon','data' => ['size' => 'w-6 h-6','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.menu-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-6 h-6','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf893dcd45b7e3cdc4c978907b5a8e5e3)): ?>
<?php $attributes = $__attributesOriginalf893dcd45b7e3cdc4c978907b5a8e5e3; ?>
<?php unset($__attributesOriginalf893dcd45b7e3cdc4c978907b5a8e5e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf893dcd45b7e3cdc4c978907b5a8e5e3)): ?>
<?php $component = $__componentOriginalf893dcd45b7e3cdc4c978907b5a8e5e3; ?>
<?php unset($__componentOriginalf893dcd45b7e3cdc4c978907b5a8e5e3); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'roles','selectedService' => $selectedService,'title' => __('Roles and permissions')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'roles','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Roles and permissions'))]); ?>
        <?php if (isset($component)) { $__componentOriginal219c6a87c192f12212d7bd92831eb996 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal219c6a87c192f12212d7bd92831eb996 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.shield-icon','data' => ['size' => 'w-7 h-7','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.shield-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-7 h-7','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal219c6a87c192f12212d7bd92831eb996)): ?>
<?php $attributes = $__attributesOriginal219c6a87c192f12212d7bd92831eb996; ?>
<?php unset($__attributesOriginal219c6a87c192f12212d7bd92831eb996); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal219c6a87c192f12212d7bd92831eb996)): ?>
<?php $component = $__componentOriginal219c6a87c192f12212d7bd92831eb996; ?>
<?php unset($__componentOriginal219c6a87c192f12212d7bd92831eb996); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'users','selectedService' => $selectedService,'title' => __('Users')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'users','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Users'))]); ?>
        <?php if (isset($component)) { $__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.users-icon','data' => ['size' => 'w-6 h-6','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.users-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-6 h-6','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031)): ?>
<?php $attributes = $__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031; ?>
<?php unset($__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031)): ?>
<?php $component = $__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031; ?>
<?php unset($__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'ranks','selectedService' => $selectedService,'title' => __('Ranks')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'ranks','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Ranks'))]); ?>
        <?php if (isset($component)) { $__componentOriginal64c47f76625c1a4edd22c4a9c0e93887 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.double-arrow-icon','data' => ['size' => 'w-6 h-6','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.double-arrow-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-6 h-6','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887)): ?>
<?php $attributes = $__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887; ?>
<?php unset($__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64c47f76625c1a4edd22c4a9c0e93887)): ?>
<?php $component = $__componentOriginal64c47f76625c1a4edd22c4a9c0e93887; ?>
<?php unset($__componentOriginal64c47f76625c1a4edd22c4a9c0e93887); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'order-documents','selectedService' => $selectedService,'title' => __('Order templates')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'order-documents','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Order templates'))]); ?>
        <?php if (isset($component)) { $__componentOriginalf859087f92900c60a10ae7e28459be22 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf859087f92900c60a10ae7e28459be22 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.document-icon','data' => ['size' => 'w-6 h-6','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.document-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-6 h-6','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf859087f92900c60a10ae7e28459be22)): ?>
<?php $attributes = $__attributesOriginalf859087f92900c60a10ae7e28459be22; ?>
<?php unset($__attributesOriginalf859087f92900c60a10ae7e28459be22); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf859087f92900c60a10ae7e28459be22)): ?>
<?php $component = $__componentOriginalf859087f92900c60a10ae7e28459be22; ?>
<?php unset($__componentOriginalf859087f92900c60a10ae7e28459be22); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalaaaf2bdc30347de99e993f0da414e001 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaaf2bdc30347de99e993f0da414e001 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.services-menu-item','data' => ['key' => 'components','selectedService' => $selectedService,'title' => __('Components')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('services-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['key' => 'components','selectedService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedService),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Components'))]); ?>
        <?php if (isset($component)) { $__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.components-icon','data' => ['size' => 'w-6 h-6','color' => 'text-green-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.components-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-6 h-6','color' => 'text-green-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd)): ?>
<?php $attributes = $__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd; ?>
<?php unset($__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd)): ?>
<?php $component = $__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd; ?>
<?php unset($__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $attributes = $__attributesOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__attributesOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaaf2bdc30347de99e993f0da414e001)): ?>
<?php $component = $__componentOriginalaaaf2bdc30347de99e993f0da414e001; ?>
<?php unset($__componentOriginalaaaf2bdc30347de99e993f0da414e001); ?>
<?php endif; ?>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/structure/services.blade.php ENDPATH**/ ?>