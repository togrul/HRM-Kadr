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
        <path d="M8.42034438,20 L21,20 C22.1045695,20 23,19.1045695 23,18 L23,6 C23,4.8954305 22.1045695,4 21,4 L8.42034438,4 C8.15668432,4 7.90369297,4.10412727 7.71642146,4.28972363 L0.653241109,11.2897236 C0.260966303,11.6784895 0.25812177,12.3116481 0.646887666,12.7039229 C0.648995955,12.7060502 0.651113791,12.7081681 0.653241109,12.7102764 L7.71642146,19.7102764 C7.90369297,19.8958727 8.15668432,20 8.42034438,20 Z" fill="currentColor" opacity="0.3"/>
        <path d="M12.5857864,12 L11.1715729,10.5857864 C10.7810486,10.1952621 10.7810486,9.56209717 11.1715729,9.17157288 C11.5620972,8.78104858 12.1952621,8.78104858 12.5857864,9.17157288 L14,10.5857864 L15.4142136,9.17157288 C15.8047379,8.78104858 16.4379028,8.78104858 16.8284271,9.17157288 C17.2189514,9.56209717 17.2189514,10.1952621 16.8284271,10.5857864 L15.4142136,12 L16.8284271,13.4142136 C17.2189514,13.8047379 17.2189514,14.4379028 16.8284271,14.8284271 C16.4379028,15.2189514 15.8047379,15.2189514 15.4142136,14.8284271 L14,13.4142136 L12.5857864,14.8284271 C12.1952621,15.2189514 11.5620972,15.2189514 11.1715729,14.8284271 C10.7810486,14.4379028 10.7810486,13.8047379 11.1715729,13.4142136 L12.5857864,12 Z" fill="currentColor"/>
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
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/icons/backspace-icon.blade.php ENDPATH**/ ?>