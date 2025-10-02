<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['filters']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['filters']); ?>
<?php foreach (array_filter((['filters']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<button @click="$wire.dispatch('setOpenFilter')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'flex relative items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-gray-100',
    'bg-gray-100' => count($filters) > 0,
]); ?>" type="button" title="Filter">
    <?php if (isset($component)) { $__componentOriginal01046fb947b9b5b0a1a7f166baac84a0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal01046fb947b9b5b0a1a7f166baac84a0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.search-file','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.search-file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal01046fb947b9b5b0a1a7f166baac84a0)): ?>
<?php $attributes = $__attributesOriginal01046fb947b9b5b0a1a7f166baac84a0; ?>
<?php unset($__attributesOriginal01046fb947b9b5b0a1a7f166baac84a0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal01046fb947b9b5b0a1a7f166baac84a0)): ?>
<?php $component = $__componentOriginal01046fb947b9b5b0a1a7f166baac84a0; ?>
<?php unset($__componentOriginal01046fb947b9b5b0a1a7f166baac84a0); ?>
<?php endif; ?>
    <!--[if BLOCK]><![endif]--><?php if(count($filters) > 0): ?>
        <span class="absolute top-0 right-0 rounded-full bg-rose-500 text-white flex justify-center w-4 h-4 text-xs">
            <?php echo e(count($filters)); ?>

        </span>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</button>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/filter-button.blade.php ENDPATH**/ ?>