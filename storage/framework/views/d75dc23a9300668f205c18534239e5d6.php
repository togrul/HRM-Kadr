<?php if (isset($component)) { $__componentOriginal47243a3de3ed132c2f9157dc8e8a8bd7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal47243a3de3ed132c2f9157dc8e8a8bd7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-delete','data' => ['livewireEventToOpenModal' => 'deleteCandidateWasSet','eventToCloseModal' => 'candidateWasDeleted','modalTitle' => __('Delete candidate'),'modalDescription' => __('Are you sure you want to delete this candidate?'),'modalConfirmButtonText' => __('Delete'),'wireClick' => 'deleteCandidate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['livewire-event-to-open-modal' => 'deleteCandidateWasSet','event-to-close-modal' => 'candidateWasDeleted','modal-title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete candidate')),'modal-description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Are you sure you want to delete this candidate?')),'modal-confirm-button-text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Delete')),'wire-click' => 'deleteCandidate']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal47243a3de3ed132c2f9157dc8e8a8bd7)): ?>
<?php $attributes = $__attributesOriginal47243a3de3ed132c2f9157dc8e8a8bd7; ?>
<?php unset($__attributesOriginal47243a3de3ed132c2f9157dc8e8a8bd7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal47243a3de3ed132c2f9157dc8e8a8bd7)): ?>
<?php $component = $__componentOriginal47243a3de3ed132c2f9157dc8e8a8bd7; ?>
<?php unset($__componentOriginal47243a3de3ed132c2f9157dc8e8a8bd7); ?>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/candidates/delete-candidate.blade.php ENDPATH**/ ?>