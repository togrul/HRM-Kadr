<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => null,
    'hover' => null,
    'size' => 'w-6 h-6',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => null,
    'hover' => null,
    'size' => 'w-6 h-6',
]); ?>
<?php foreach (array_filter(([
    'color' => null,
    'hover' => null,
    'size' => 'w-6 h-6',
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.root','data' => ['color' => $color,'hover' => $hover,'size' => $size]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.root'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover),'size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size)]); ?>
    <g fill="none">
        <path fill="currentColor"
            d="M4 10c0-1.886 0-2.828.586-3.414C5.172 6 6.114 6 8 6h8c1.886 0 2.828 0 3.414.586C20 7.172 20 8.114 20 10v2c0 .943 0 1.414-.293 1.707C19.414 14 18.943 14 18 14h-.7c-.141 0-.212 0-.256-.044C17 13.912 17 13.841 17 13.7V13c0-.943 0-1.414-.293-1.707C16.414 11 15.943 11 15 11H9c-.943 0-1.414 0-1.707.293C7 11.586 7 12.057 7 13v.7c0 .141 0 .212-.044.256C6.912 14 6.841 14 6.7 14H5c-.471 0-.707 0-.854-.146C4 13.707 4 13.47 4 13z" />
        <path fill="currentColor" fill-opacity=".25"
            d="M7 20.262V13c0-.943 0-1.414.293-1.707C7.586 11 8.057 11 9 11h6c.943 0 1.414 0 1.707.293c.293.293.293.764.293 1.707v7.262c0 .334 0 .501-.11.576c-.11.074-.265.012-.576-.112l-1.628-.652a.53.53 0 0 0-.186-.055a.53.53 0 0 0-.186.055l-2.128.852a.53.53 0 0 1-.186.055a.53.53 0 0 1-.186-.055l-2.128-.852a.53.53 0 0 0-.186-.055a.53.53 0 0 0-.186.055l-1.628.652c-.311.124-.466.186-.576.112c-.11-.075-.11-.242-.11-.576" />
        <path stroke="currentColor" stroke-linecap="round" d="M9.5 14.5h4m-4 3h5" />
        <path fill="currentColor"
            d="M7 4.74c0-.693 0-1.039.164-1.288a1 1 0 0 1 .288-.288C7.702 3 8.047 3 8.739 3h6.522c.692 0 1.038 0 1.288.164a1 1 0 0 1 .287.288c.164.25.164.595.164 1.287c0 .104 0 .156-.025.193a.149.149 0 0 1-.043.043C16.895 5 16.842 5 16.74 5H7.261c-.104 0-.156 0-.193-.025a.15.15 0 0 1-.043-.043C7 4.895 7 4.842 7 4.74" />
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
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/icons/print-file.blade.php ENDPATH**/ ?>