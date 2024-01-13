<?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-delete','data' => ['livewireEventToOpenModal' => 'deleteMenuWasSet','eventToCloseModal' => 'menuWasDeleted','modalTitle' => __('Delete menu'),'modalDescription' => __('Are you sure you want to delete this menu? This action cannot be undone.'),'modalConfirmButtonText' => __('Delete'),'wireClick' => 'deleteMenu']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['livewire-event-to-open-modal' => 'deleteMenuWasSet','event-to-close-modal' => 'menuWasDeleted','modal-title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete menu')),'modal-description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Are you sure you want to delete this menu? This action cannot be undone.')),'modal-confirm-button-text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete')),'wire-click' => 'deleteMenu']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/services/menus/delete-menu.blade.php ENDPATH**/ ?>