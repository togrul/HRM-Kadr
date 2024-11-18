<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-rose-500',
    'hover' => 'text-rose-600',
    'size' => 'w-6 h-6'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-rose-500',
    'hover' => 'text-rose-600',
    'size' => 'w-6 h-6'
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-rose-500',
    'hover' => 'text-rose-600',
    'size' => 'w-6 h-6'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php if (isset($component)) { $__componentOriginal601d5ec60962498d7a3a1e9e26ae4987 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal601d5ec60962498d7a3a1e9e26ae4987 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.root','data' => ['size' => $size,'color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.root'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <rect x="0" y="0" width="24" height="24"/>
        <path d="M2.76702366,20 C2.59202225,20 2.4200849,19.9540749 2.2683913,19.8668136 C1.78966338,19.5914265 1.62482304,18.9800956 1.90021009,18.5013676 L11.1332403,2.45083309 C11.221302,2.29774818 11.3483346,2.17071522 11.5014193,2.08265312 C11.9801465,1.80726488 12.5914779,1.97210369 12.8668662,2.45083092 L22.0999499,18.5013655 C22.187212,18.6530596 22.2331375,18.8249977 22.2331375,19 C22.2331375,19.5522847 21.7854223,20 21.2331375,20 L2.76702366,20 Z M11,18 L15,12 L12.9444444,12 L12.9444444,8 L9,14 L11,14 L11,18 Z" fill="currentColor"/>
    </g>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal601d5ec60962498d7a3a1e9e26ae4987)): ?>
<?php $attributes = $__attributesOriginal601d5ec60962498d7a3a1e9e26ae4987; ?>
<?php unset($__attributesOriginal601d5ec60962498d7a3a1e9e26ae4987); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal601d5ec60962498d7a3a1e9e26ae4987)): ?>
<?php $component = $__componentOriginal601d5ec60962498d7a3a1e9e26ae4987; ?>
<?php unset($__componentOriginal601d5ec60962498d7a3a1e9e26ae4987); ?>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/icons/voltage-icon.blade.php ENDPATH**/ ?>