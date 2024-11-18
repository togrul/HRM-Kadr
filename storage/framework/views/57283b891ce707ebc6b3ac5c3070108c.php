<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.root','data' => ['animated' => 'true','xShow' => ''.e($show ?? 'true').'','size' => $size,'color' => $color,'hover' => $hover]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.root'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['animated' => 'true','x-show' => ''.e($show ?? 'true').'','size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover)]); ?>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <rect x="0" y="0" width="24" height="24"/>
        <path d="M5.84026576,8 L18.1597342,8 C19.1999115,8 20.0664437,8.79732479 20.1528258,9.83390904 L20.8194924,17.833909 C20.9112219,18.9346631 20.0932459,19.901362 18.9924919,19.9930915 C18.9372479,19.9976952 18.8818364,20 18.8264009,20 L5.1735991,20 C4.0690296,20 3.1735991,19.1045695 3.1735991,18 C3.1735991,17.9445645 3.17590391,17.889153 3.18050758,17.833909 L3.84717425,9.83390904 C3.93355627,8.79732479 4.80008849,8 5.84026576,8 Z M10.5,10 C10.2238576,10 10,10.2238576 10,10.5 L10,11.5 C10,11.7761424 10.2238576,12 10.5,12 L13.5,12 C13.7761424,12 14,11.7761424 14,11.5 L14,10.5 C14,10.2238576 13.7761424,10 13.5,10 L10.5,10 Z" fill="currentColor"/>
        <path d="M10,8 L8,8 L8,7 C8,5.34314575 9.34314575,4 11,4 L13,4 C14.6568542,4 16,5.34314575 16,7 L16,8 L14,8 L14,7 C14,6.44771525 13.5522847,6 13,6 L11,6 C10.4477153,6 10,6.44771525 10,7 L10,8 Z" fill="currentColor" fill-rule="nonzero" opacity="0.3"/>
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
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/icons/briefcase-icon.blade.php ENDPATH**/ ?>