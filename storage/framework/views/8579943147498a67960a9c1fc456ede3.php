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
<?php if (isset($component)) { $__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.users-icon','data' => ['color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.users-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031)): ?>
<?php $attributes = $__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031; ?>
<?php unset($__attributesOriginal51e5001cfb9cbe5d7fd208b1dc679031); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031)): ?>
<?php $component = $__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031; ?>
<?php unset($__componentOriginal51e5001cfb9cbe5d7fd208b1dc679031); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/storage/framework/views/c577a413c3a46f252914223a9475d64a.blade.php ENDPATH**/ ?>