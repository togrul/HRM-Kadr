<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title',
    'checkbox' => null,
    'checkboxTitle' => null,
    'type' => 'simple'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title',
    'checkbox' => null,
    'checkboxTitle' => null,
    'type' => 'simple'
]); ?>
<?php foreach (array_filter(([
    'title',
    'checkbox' => null,
    'checkboxTitle' => null,
    'type' => 'simple'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>



<div
    data-slot="card-container"
    class="w-full rounded-xl border border-neutral-200/70 bg-neutral-50 p-1.5 dark:border-white/5 dark:bg-white/3 flex flex-col gap-2 h-max">
    <div class="flex items-center justify-between p-2 pb-1.5">
        <div class="flex items-center gap-2.5">
            
            
            <h3 class="text-base font-medium"><?php echo e(__($title) ?? ''); ?></h3>
             <!--[if BLOCK]><![endif]--><?php if($checkbox): ?>
                <?php if (isset($component)) { $__componentOriginal74b62b190a03153f11871f645315f4de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b62b190a03153f11871f645315f4de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.checkbox','data' => ['name' => ''.e($checkbox).'','model' => ''.e($checkbox).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => ''.e($checkbox).'','model' => ''.e($checkbox).'']); ?><?php echo e(__($checkboxTitle)); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $attributes = $__attributesOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__attributesOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b62b190a03153f11871f645315f4de)): ?>
<?php $component = $__componentOriginal74b62b190a03153f11871f645315f4de; ?>
<?php unset($__componentOriginal74b62b190a03153f11871f645315f4de); ?>
<?php endif; ?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </div>
    <div data-slot="card"
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'border-neutral-200 bg-white shadow-md shadow-black/5 dark:border-white/5 dark:bg-white/2 dark:shadow-black/20 flex h-full shrink-0 snap-center flex-col justify-between gap-6 rounded-lg border p-4 w-full md:p-6' => $type == 'simple',
            'divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white shadow-md shadow-black/5 dark:divide-white/8 dark:border-white/8 dark:bg-white/3' => $type == 'divided',
        ]); ?>"
    >
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/form-card.blade.php ENDPATH**/ ?>