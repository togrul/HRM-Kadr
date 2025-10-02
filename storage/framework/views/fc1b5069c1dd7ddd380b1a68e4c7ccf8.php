<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['model', 'level' => 0]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['model', 'level' => 0]); ?>
<?php foreach (array_filter((['model', 'level' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $hasSubs = $model->subs->isNotEmpty();
    $isSelected = $model->id === $this->selectedStructure;
?>

<li
    class="relative py-1 overflow-hidden"
    x-data="{ openSub: true }"
>
    <!--[if BLOCK]><![endif]--><?php if($hasSubs): ?>
        <span class="absolute top-8 left-2.5 w-px h-full bg-neutral-300" x-show="openSub" x-cloak></span>
    <?php else: ?>
        <span class="absolute top-[14px] left-0 flex items-center">
            <span class="h-px w-5 bg-neutral-300"></span>
            <span class="h-1.5 w-1.5 top-[2px] rounded-full bg-neutral-500"></span>
        </span>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <div class="flex flex-col">
            <div class="flex items-center gap-2">
                <div class="flex flex-none items-center">
                    <!--[if BLOCK]><![endif]--><?php if($hasSubs): ?>
                        <button
                            type="button"
                            @click="openSub = !openSub"
                            @keydown.enter.prevent="openSub = !openSub"
                            @keydown.space.prevent="openSub = !openSub"
                            :aria-expanded="openSub.toString()"
                            aria-controls="subs-<?php echo e($model->id); ?>"
                            class="rounded focus:outline-none"
                        >
                            <?php echo $__env->make('components.icons.chevron-right-icon', [
                                'show'  => '!openSub',
                                'size'  => 'w-5 h-5',
                                'color' => 'text-slate-500',
                                'hover' => 'text-slate-600'
                            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php echo $__env->make('components.icons.chevron-down-icon', [
                                'show'  => 'openSub',
                                'size'  => 'w-5 h-5',
                                'color' => 'text-slate-500',
                                'hover' => 'text-slate-600'
                            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </button>
                    <?php else: ?>
                        <span class="w-7 h-7"></span>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
            <div class="flex-1 min-w-0">
                <button
                    type="button"
                    wire:click.prevent="selectStructure(<?php echo e($model->id); ?>)"
                    wire:key="node-<?php echo e($model->id); ?>"
                    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'font-medium appearance-none transition-colors duration-200 text-left focus:outline-none',
                        'text-blue-500' => $isSelected,
                        'text-gray-600' => !$isSelected,
                    ]); ?>"
                >
                    <?php echo e($slot); ?>

                </button>
            </div>
        </div>
            <!--[if BLOCK]><![endif]--><?php if($hasSubs): ?>
                <ul
                    id="subs-<?php echo e($model->id); ?>"
                    class="ml-[13px] flex flex-col"
                    x-show="openSub"
                    x-collapse
                    x-cloak
                >
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if (isset($component)) { $__componentOriginal87307df77d7d1f3615061102a769de13 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal87307df77d7d1f3615061102a769de13 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tree.item','data' => ['model' => $sub,'level' => $level + 1,'wire:key' => 'node-'.e($sub->id).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('tree.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sub),'level' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($level + 1),'wire:key' => 'node-'.e($sub->id).'']); ?>
                            <?php echo e($sub->name); ?>

                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal87307df77d7d1f3615061102a769de13)): ?>
<?php $attributes = $__attributesOriginal87307df77d7d1f3615061102a769de13; ?>
<?php unset($__attributesOriginal87307df77d7d1f3615061102a769de13); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal87307df77d7d1f3615061102a769de13)): ?>
<?php $component = $__componentOriginal87307df77d7d1f3615061102a769de13; ?>
<?php unset($__componentOriginal87307df77d7d1f3615061102a769de13); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                </ul>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</li>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/tree/item.blade.php ENDPATH**/ ?>