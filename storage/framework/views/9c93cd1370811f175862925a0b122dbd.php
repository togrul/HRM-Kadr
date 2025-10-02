<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
]); ?>
<?php foreach (array_filter(([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
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
    <g fill="currentColor">
        <path
            d="M3 13.037c0-1.103 0-1.655.393-1.976c.139-.114.308-.206.497-.269c.532-.177 1.231-.002 2.629.346c1.067.267 1.6.4 2.14.386c.198-.005.395-.025.588-.059c.525-.093.993-.326 1.929-.793l1.382-.69c1.2-.599 1.799-.898 2.487-.967c.688-.069 1.372.102 2.739.443l1.165.29c.99.247 1.485.371 1.768.665c.283.294.283.685.283 1.466v6.084c0 1.103 0 1.655-.393 1.976a1.563 1.563 0 0 1-.497.269c-.532.177-1.231.003-2.629-.346c-1.067-.267-1.6-.4-2.14-.386a3.951 3.951 0 0 0-.588.059c-.525.093-.993.326-1.929.793l-1.382.69c-1.2.599-1.799.898-2.487.967c-.688.069-1.372-.102-2.739-.443l-1.165-.29c-.99-.247-1.485-.371-1.768-.665C3 20.293 3 19.902 3 19.121v-6.084Z"
            opacity=".5" />
        <path fill-rule="evenodd"
            d="M12 2C8.686 2 6 4.552 6 7.7c0 3.124 1.915 6.769 4.903 8.072a2.755 2.755 0 0 0 2.194 0C16.085 14.47 18 10.824 18 7.7C18 4.552 15.314 2 12 2Zm0 8a2 2 0 1 0 0-4a2 2 0 0 0 0 4Z"
            clip-rule="evenodd" />
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
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/icons/vacation-icon.blade.php ENDPATH**/ ?>