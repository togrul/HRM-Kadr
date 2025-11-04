<?php if (isset($component)) { $__componentOriginal385415170eeee0a3f5ca885323726d52 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal385415170eeee0a3f5ca885323726d52 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.confirmation-modal','data' => ['title' => 'Add comment','confirm' => 'Save','cancel' => 'Cancel','confirmAction' => 'confirmComment']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.confirmation-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Add comment','confirm' => 'Save','cancel' => 'Cancel','confirmAction' => 'confirmComment']); ?>
    <div class="flex flex-col">
        <label for="comment-ta" class="sr-only">Şərh</label>
        <?php if (isset($component)) { $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.textarea','data' => ['xRef' => 'ta','name' => 'comment','mode' => 'gray','xModel' => 'comment','class' => 'w-full min-h-[140px] ... ','placeholder' => 'Comment']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-ref' => 'ta','name' => 'comment','mode' => 'gray','x-model' => 'comment','class' => 'w-full min-h-[140px] ... ','placeholder' => 'Comment']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $attributes = $__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__attributesOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886)): ?>
<?php $component = $__componentOriginal4727f9fd7c3055c2cf9c658d89b16886; ?>
<?php unset($__componentOriginal4727f9fd7c3055c2cf9c658d89b16886); ?>
<?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal385415170eeee0a3f5ca885323726d52)): ?>
<?php $attributes = $__attributesOriginal385415170eeee0a3f5ca885323726d52; ?>
<?php unset($__attributesOriginal385415170eeee0a3f5ca885323726d52); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal385415170eeee0a3f5ca885323726d52)): ?>
<?php $component = $__componentOriginal385415170eeee0a3f5ca885323726d52; ?>
<?php unset($__componentOriginal385415170eeee0a3f5ca885323726d52); ?>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/confirmation/add-comment.blade.php ENDPATH**/ ?>