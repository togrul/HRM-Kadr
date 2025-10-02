<?php if (isset($component)) { $__componentOriginal189560b6b7fd169765d112fc0397f9f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal189560b6b7fd169765d112fc0397f9f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tree.list','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('tree.list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $structures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $structure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if (isset($component)) { $__componentOriginal87307df77d7d1f3615061102a769de13 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal87307df77d7d1f3615061102a769de13 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tree.item','data' => ['model' => $structure]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('tree.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($structure)]); ?><?php echo e($structure->name); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal87307df77d7d1f3615061102a769de13)): ?>
<?php $attributes = $__attributesOriginal87307df77d7d1f3615061102a769de13; ?>
<?php unset($__attributesOriginal87307df77d7d1f3615061102a769de13); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal87307df77d7d1f3615061102a769de13)): ?>
<?php $component = $__componentOriginal87307df77d7d1f3615061102a769de13; ?>
<?php unset($__componentOriginal87307df77d7d1f3615061102a769de13); ?>
<?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal189560b6b7fd169765d112fc0397f9f5)): ?>
<?php $attributes = $__attributesOriginal189560b6b7fd169765d112fc0397f9f5; ?>
<?php unset($__attributesOriginal189560b6b7fd169765d112fc0397f9f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal189560b6b7fd169765d112fc0397f9f5)): ?>
<?php $component = $__componentOriginal189560b6b7fd169765d112fc0397f9f5; ?>
<?php unset($__componentOriginal189560b6b7fd169765d112fc0397f9f5); ?>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/structure/sidebar.blade.php ENDPATH**/ ?>