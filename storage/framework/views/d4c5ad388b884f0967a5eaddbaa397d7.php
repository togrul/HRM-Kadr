<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-6 h-6',
    'show' => null
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-6 h-6',
    'show' => null
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-6 h-6',
    'show' => null
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.root','data' => ['animated' => 'true','xShow' => ''.e($show).'','size' => $size,'color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.root'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['animated' => 'true','x-show' => ''.e($show).'','size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <polygon points="0 0 24 0 24 24 0 24"/>
        <rect fill="currentColor" opacity="0.3" transform="translate(12.000000, 12.000000) rotate(-90.000000) translate(-12.000000, -12.000000) " x="7" y="11" width="10" height="2" rx="1"/>
        <path d="M6.70710678,8.70710678 C6.31658249,9.09763107 5.68341751,9.09763107 5.29289322,8.70710678 C4.90236893,8.31658249 4.90236893,7.68341751 5.29289322,7.29289322 L11.2928932,1.29289322 C11.6714722,0.914314282 12.2810586,0.90106866 12.6757246,1.26284586 L18.6757246,6.76284586 C19.0828436,7.13603827 19.1103465,7.76860564 18.7371541,8.17572463 C18.3639617,8.58284362 17.7313944,8.61034655 17.3242754,8.23715414 L12.0300757,3.38413782 L6.70710678,8.70710678 Z" fill="currentColor" fill-rule="nonzero"/>
        <path d="M6.70710678,22.7071068 C6.31658249,23.0976311 5.68341751,23.0976311 5.29289322,22.7071068 C4.90236893,22.3165825 4.90236893,21.6834175 5.29289322,21.2928932 L11.2928932,15.2928932 C11.6714722,14.9143143 12.2810586,14.9010687 12.6757246,15.2628459 L18.6757246,20.7628459 C19.0828436,21.1360383 19.1103465,21.7686056 18.7371541,22.1757246 C18.3639617,22.5828436 17.7313944,22.6103465 17.3242754,22.2371541 L12.0300757,17.3841378 L6.70710678,22.7071068 Z" fill="currentColor" fill-rule="nonzero" transform="translate(12.000003, 18.999999) rotate(-180.000000) translate(-12.000003, -18.999999) "/>
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
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/icons/arrow-icon.blade.php ENDPATH**/ ?>