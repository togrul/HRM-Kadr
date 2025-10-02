<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['statusId', 'label', 'type' => null, 'design' => 'default']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['statusId', 'label', 'type' => null, 'design' => 'default']); ?>
<?php foreach (array_filter((['statusId', 'label', 'type' => null, 'design' => 'default']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $map = [
        10 => 'bg-neutral-200/60 text-neutral-600 border-neutral-200',
        20 => 'bg-amber-50 border-amber-200 text-amber-600',
        30 => 'bg-sky-50 border-sky-200 text-sky-600',
        40 => 'bg-indigo-50 border-indigo-200 text-indigo-600',
        70 => 'bg-emerald-50 border-emerald-200 text-emerald-600',
        90 => 'bg-rose-50 border-rose-200 text-rose-600',
    ];

    if ($type === 'order') {
        $statusId = match ($statusId) {
            10 => 10,
            20 => 70,
            30 => 90,
            default => $statusId,
        };
    }

    $color = $map[$statusId] ?? 'bg-slate-50 text-slate-600 border-slate-200';

    $iconMap = [
        10 => 'icons.timer-icon',   // nümunə
        20 => 'icons.clock-icon',
        30 => 'icons.info-icon',
        40 => 'icons.sparkle-icon',
        70 => 'icons.check-icon',
        90 => 'icons.x-circle-icon',
    ];
    $iconComponent = $iconMap[$statusId] ?? null;
?>

<!--[if BLOCK]><![endif]--><?php if($design == 'default'): ?>
<span class="text-xs border uppercase font-medium px-3 py-2 rounded-lg w-max <?php echo e($color); ?>">
    <?php echo e($label); ?>

</span>
<?php else: ?>
<span class="inline-flex w-max items-center gap-1.5 rounded-full px-2.5 py-1 font-medium text-xs uppercase tracking-wide border <?php echo e($color); ?>">
        <!--[if BLOCK]><![endif]--><?php if($iconComponent): ?>
            <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $iconComponent] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\DynamicComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-5 h-5','color' => 'text-current']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <span><?php echo e($label); ?></span>
</span>
<?php endif; ?><!--[if ENDBLOCK]><![endif]-->

<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/status.blade.php ENDPATH**/ ?>