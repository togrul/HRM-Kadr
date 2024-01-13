<?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-delete','data' => ['livewireEventToOpenModal' => 'deleteStaffWasSet','eventToCloseModal' => 'staffWasDeleted','modalTitle' => __('Delete staff'),'modalDescription' => __('Are you sure you want to delete this data?'),'modalConfirmButtonText' => __('Delete'),'wireClick' => 'deleteStaff']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['livewire-event-to-open-modal' => 'deleteStaffWasSet','event-to-close-modal' => 'staffWasDeleted','modal-title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete staff')),'modal-description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Are you sure you want to delete this data?')),'modal-confirm-button-text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete')),'wire-click' => 'deleteStaff']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/staff-schedule/delete-staff.blade.php ENDPATH**/ ?>