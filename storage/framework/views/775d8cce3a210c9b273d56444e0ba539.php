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

<li class="py-1" x-data="{openSub:true}">
    <a class="flex items-center space-x-2">
        <!--[if BLOCK]><![endif]--><?php if($model->subs->isNotEmpty()): ?>
            <button @click="openSub = !openSub" class="rounded-lg bg-blue-100 text-blue-500 p-1 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path x-show="openSub"
                        stroke-linecap="round"
                        stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                    />
                    <path
                        x-show="!openSub"
                        stroke-linecap="round"
                        stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                    />
                  </svg>
            </button>
        <?php else: ?>
            <span class="w-7 h-7"></span>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <button
            wire:click.prevent="selectStructure(<?php echo e($model->id); ?>)"
            class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'font-medium appearance-none transition-all duration-300 text-left',
                'text-blue-500' => $model->id == $this->selectedStructure
            ]); ?>"
            >
            <?php echo e($slot); ?>

        </button>
    </a>
    <!--[if BLOCK]><![endif]--><?php if($model->subs->isNotEmpty()): ?>
        <ul class="ml-4 flex-col flex" x-show="openSub">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                 <?php if (isset($component)) { $__componentOriginal87307df77d7d1f3615061102a769de13 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal87307df77d7d1f3615061102a769de13 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tree.item','data' => ['model' => $sub]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('tree.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sub)]); ?> - <?php echo e($sub->name); ?> <?php echo $__env->renderComponent(); ?>
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
</li>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/tree/item.blade.php ENDPATH**/ ?>