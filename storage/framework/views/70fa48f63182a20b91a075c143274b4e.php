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
<?php if (isset($component)) { $__componentOriginal2c0382cebda853a550091a02999e5afd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2c0382cebda853a550091a02999e5afd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.x-circle-icon','data' => ['size' => $size,'color' => $color]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.x-circle-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color)]); ?>

<?php echo e($slot ?? ""); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2c0382cebda853a550091a02999e5afd)): ?>
<?php $attributes = $__attributesOriginal2c0382cebda853a550091a02999e5afd; ?>
<?php unset($__attributesOriginal2c0382cebda853a550091a02999e5afd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2c0382cebda853a550091a02999e5afd)): ?>
<?php $component = $__componentOriginal2c0382cebda853a550091a02999e5afd; ?>
<?php unset($__componentOriginal2c0382cebda853a550091a02999e5afd); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/storage/framework/views/0c8f796537a3bbed366f8d2e5fd98f82.blade.php ENDPATH**/ ?>