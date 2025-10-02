<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-slate-500',
    'hover' => 'text-slate-600',
    'size' => 'w-6 h-6',
    'show' => null
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-slate-500',
    'hover' => 'text-slate-600',
    'size' => 'w-6 h-6',
    'show' => null
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-slate-500',
    'hover' => 'text-slate-600',
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.root','data' => ['size' => $size,'color' => $color,'hover' => $hover,'xShow' => ''.e($show ?? 'true').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.root'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($size),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'hover' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hover),'x-show' => ''.e($show ?? 'true').'']); ?>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <polygon points="0 0 24 0 24 24 0 24"/>
        <rect fill="currentColor" opacity="0.3" transform="translate(10.000000, 12.000000) rotate(-90.000000) translate(-10.000000, -12.000000) " x="9" y="5" width="2" height="14" rx="1"/>
        <rect fill="currentColor" opacity="0.3" x="19" y="3" width="2" height="18" rx="1"/>
        <path d="M7.70710318,15.7071045 C7.31657888,16.0976288 6.68341391,16.0976288 6.29288961,15.7071045 C5.90236532,15.3165802 5.90236532,14.6834152 6.29288961,14.2928909 L12.2928896,8.29289093 C12.6714686,7.914312 13.281055,7.90106637 13.675721,8.26284357 L19.675721,13.7628436 C20.08284,14.136036 20.1103429,14.7686034 19.7371505,15.1757223 C19.3639581,15.5828413 18.7313908,15.6103443 18.3242718,15.2371519 L13.0300721,10.3841355 L7.70710318,15.7071045 Z" fill="currentColor" fill-rule="nonzero" transform="translate(12.999999, 11.999997) scale(1, -1) rotate(90.000000) translate(-12.999999, -11.999997) "/>
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
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/icons/right-icon.blade.php ENDPATH**/ ?>