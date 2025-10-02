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
<?php if (isset($component)) { $__componentOriginalf0c6472a6fe5dd1eb97710caff505d07 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.close-icon','data' => ['size' => $size,'color' => $color]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.close-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07)): ?>
<?php $attributes = $__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07; ?>
<?php unset($__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c6472a6fe5dd1eb97710caff505d07)): ?>
<?php $component = $__componentOriginalf0c6472a6fe5dd1eb97710caff505d07; ?>
<?php unset($__componentOriginalf0c6472a6fe5dd1eb97710caff505d07); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/storage/framework/views/cd06ee7e2a6e1fb230e3d9e3d261419a.blade.php ENDPATH**/ ?>