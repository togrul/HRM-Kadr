<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['color','hover']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['color','hover']); ?>
<?php foreach (array_filter((['color','hover']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<?php if (isset($component)) { $__componentOriginalaec190c712cc00e0c0210cfc111d6c3a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaec190c712cc00e0c0210cfc111d6c3a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.road-icon','data' => ['color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.road-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaec190c712cc00e0c0210cfc111d6c3a)): ?>
<?php $attributes = $__attributesOriginalaec190c712cc00e0c0210cfc111d6c3a; ?>
<?php unset($__attributesOriginalaec190c712cc00e0c0210cfc111d6c3a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaec190c712cc00e0c0210cfc111d6c3a)): ?>
<?php $component = $__componentOriginalaec190c712cc00e0c0210cfc111d6c3a; ?>
<?php unset($__componentOriginalaec190c712cc00e0c0210cfc111d6c3a); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/storage/framework/views/fbd95c08e7920d6128ab969b551778a9.blade.php ENDPATH**/ ?>