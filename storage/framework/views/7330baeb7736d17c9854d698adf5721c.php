<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['model']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['model']); ?>
<?php foreach (array_filter((['model']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<li class="py-1 w-full">
    <a class="flex items-center space-x-2 relative">
        <!--[if BLOCK]><![endif]--><?php if($model->parent_id): ?>
        <div class="w-9 h-full z-0 absolute -left-4 -top-6 rounded-b-md border-b-2 border-l-2 border-gray-300">

        </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <div
            class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'font-medium bg-gray-100 z-[2] rounded-lg shadow-sm px-3 py-2 w-full text-gray-600 appearance-none transition-all duration-300 text-left flex justify-between items-center'
            ]); ?>"
        >
           <span> <?php echo e($slot); ?></span>
            <div class="flex items-center space-x-2">
                <button
                    wire:click.prevent="openCrud(<?php echo e($model->id); ?>)"
                    class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                >
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
                </button>
                <button
                    wire:click = "deleteModel(<?php echo e($model->id); ?>)"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
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
            </div>
        </div>
    </a>
    <!--[if BLOCK]><![endif]--><?php if($model->subs->isNotEmpty()): ?>
        <ul class="ml-6 flex-col flex">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginal14057afbbbd49a10f5181cc96dcd1757 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal14057afbbbd49a10f5181cc96dcd1757 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.nested.item','data' => ['model' => $sub]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('nested.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sub)]); ?><?php echo e($sub->name); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal14057afbbbd49a10f5181cc96dcd1757)): ?>
<?php $attributes = $__attributesOriginal14057afbbbd49a10f5181cc96dcd1757; ?>
<?php unset($__attributesOriginal14057afbbbd49a10f5181cc96dcd1757); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal14057afbbbd49a10f5181cc96dcd1757)): ?>
<?php $component = $__componentOriginal14057afbbbd49a10f5181cc96dcd1757; ?>
<?php unset($__componentOriginal14057afbbbd49a10f5181cc96dcd1757); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </ul>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</li>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/nested/item.blade.php ENDPATH**/ ?>