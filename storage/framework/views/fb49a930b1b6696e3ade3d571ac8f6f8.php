<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['size','color']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['size','color']); ?>
<?php foreach (array_filter((['size','color']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal21ca44d7e3466d1b3f7043cca1a7159b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal21ca44d7e3466d1b3f7043cca1a7159b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.voltage-icon','data' => ['size' => $size,'color' => $color]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.voltage-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal21ca44d7e3466d1b3f7043cca1a7159b)): ?>
<?php $attributes = $__attributesOriginal21ca44d7e3466d1b3f7043cca1a7159b; ?>
<?php unset($__attributesOriginal21ca44d7e3466d1b3f7043cca1a7159b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal21ca44d7e3466d1b3f7043cca1a7159b)): ?>
<?php $component = $__componentOriginal21ca44d7e3466d1b3f7043cca1a7159b; ?>
<?php unset($__componentOriginal21ca44d7e3466d1b3f7043cca1a7159b); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/storage/framework/views/b3154aa160c33811d9aee285a0852e5e.blade.php ENDPATH**/ ?>