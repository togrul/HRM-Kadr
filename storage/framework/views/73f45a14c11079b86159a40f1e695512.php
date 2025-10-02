<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['size','color','hover']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['size','color','hover']); ?>
<?php foreach (array_filter((['size','color','hover']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginal3b29863ca6c763a42daeb7da6d628a8a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.recover','data' => ['size' => $size,'color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.recover'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a)): ?>
<?php $attributes = $__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a; ?>
<?php unset($__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b29863ca6c763a42daeb7da6d628a8a)): ?>
<?php $component = $__componentOriginal3b29863ca6c763a42daeb7da6d628a8a; ?>
<?php unset($__componentOriginal3b29863ca6c763a42daeb7da6d628a8a); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/storage/framework/views/62094f0144044713aac77a7b7acd6db9.blade.php ENDPATH**/ ?>