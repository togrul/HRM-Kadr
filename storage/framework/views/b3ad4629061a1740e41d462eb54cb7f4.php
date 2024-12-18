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
<?php if (isset($component)) { $__componentOriginal64c47f76625c1a4edd22c4a9c0e93887 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.double-arrow-icon','data' => ['color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.double-arrow-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887)): ?>
<?php $attributes = $__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887; ?>
<?php unset($__attributesOriginal64c47f76625c1a4edd22c4a9c0e93887); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64c47f76625c1a4edd22c4a9c0e93887)): ?>
<?php $component = $__componentOriginal64c47f76625c1a4edd22c4a9c0e93887; ?>
<?php unset($__componentOriginal64c47f76625c1a4edd22c4a9c0e93887); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/storage/framework/views/80f17a3b047c9d0308c60647b85a77ff.blade.php ENDPATH**/ ?>