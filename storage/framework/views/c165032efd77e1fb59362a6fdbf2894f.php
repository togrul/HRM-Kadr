<div>
  <?php if (isset($component)) { $__componentOriginal44a7a1d6b33cb19797fb1d49cc6fc857 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal44a7a1d6b33cb19797fb1d49cc6fc857 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-confirm-lg','data' => ['livewireEventToOpenModal' => 'openFilterWasSet','eventToCloseModal' => 'filterSelected','modalTitle' => __('All filters'),'modalConfirmButtonText' => __('Search'),'wireClick' => 'search']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-confirm-lg'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['livewire-event-to-open-modal' => 'openFilterWasSet','event-to-close-modal' => 'filterSelected','modal-title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('All filters')),'modal-confirm-button-text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Search')),'wire-click' => 'search']); ?>
    <div class="grid grid-cols-3 gap-2 lg:grid-cols-5">
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.surname']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.surname']); ?><?php echo e(__('Surname')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.surname','wire:model.defer' => 'filter.surname']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.surname','wire:model.defer' => 'filter.surname']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.name']); ?><?php echo e(__('Name')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.name','wire:model.defer' => 'filter.name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.name','wire:model.defer' => 'filter.name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.patronymic']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.patronymic']); ?><?php echo e(__('Patronymic')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.patronymic','wire:model.defer' => 'filter.patronymic']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.patronymic','wire:model.defer' => 'filter.patronymic']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Structure')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.live' => 'filter.structure_id','model' => $this->structureOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Structure')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.live' => 'filter.structure_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->structureOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchStructure','wire:model.live.debounce.300ms' => 'searchStructure','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchStructure','wire:model.live.debounce.300ms' => 'searchStructure','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
          </div>
          <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Position')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.position_id','model' => $this->positions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Position')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.position_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->positions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchPosition','wire:model.live' => 'searchPosition','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchPosition','wire:model.live' => 'searchPosition','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
          </div>
        <div class="flex flex-col w-full space-y-1">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.gender']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.gender']); ?><?php echo e(__('Gender')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex flex-col">
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = \App\Enums\GenderEnum::genderOptions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal8b0f4074a714ea12b5feae7d4179e520 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b0f4074a714ea12b5feae7d4179e520 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.radio','data' => ['model' => 'filter.gender','value' => $value,'label' => $label]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.radio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => 'filter.gender','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b0f4074a714ea12b5feae7d4179e520)): ?>
<?php $attributes = $__attributesOriginal8b0f4074a714ea12b5feae7d4179e520; ?>
<?php unset($__attributesOriginal8b0f4074a714ea12b5feae7d4179e520); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b0f4074a714ea12b5feae7d4179e520)): ?>
<?php $component = $__componentOriginal8b0f4074a714ea12b5feae7d4179e520; ?>
<?php unset($__componentOriginal8b0f4074a714ea12b5feae7d4179e520); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            </div>
          </div>
          <div class="flex flex-col lg:col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.birthdate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.birthdate']); ?><?php echo e(__('Birthdate')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex items-center space-x-1">
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.birthdate.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.birthdate.min']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.birthdate.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.birthdate.min']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.birthdate.min', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
                <span>-</span>
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.birthdate.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.birthdate.max']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.birthdate.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.birthdate.max']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.birthdate.max', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
            </div>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.birthdate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.birthdate']); ?><?php echo e(__('Age')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex items-center space-x-1">
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.age.min','wire:model.defer' => 'filter.age.min']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.age.min','wire:model.defer' => 'filter.age.min']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
                <span>-</span>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.age.max','wire:model.defer' => 'filter.age.max']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.age.max','wire:model.defer' => 'filter.age.max']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            </div>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.tabel_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.tabel_no']); ?><?php echo e(__('Tabel #')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.tabel_no','wire:model.defer' => 'filter.tabel_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.tabel_no','wire:model.defer' => 'filter.tabel_no']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col gap-1">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.is_married']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.is_married']); ?><?php echo e(__('Family status')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex flex-row">
                 <?php if (isset($component)) { $__componentOriginal8b0f4074a714ea12b5feae7d4179e520 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b0f4074a714ea12b5feae7d4179e520 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.radio','data' => ['model' => 'filter.is_married','value' => '0','label' => 'Single']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.radio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => 'filter.is_married','value' => '0','label' => 'Single']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b0f4074a714ea12b5feae7d4179e520)): ?>
<?php $attributes = $__attributesOriginal8b0f4074a714ea12b5feae7d4179e520; ?>
<?php unset($__attributesOriginal8b0f4074a714ea12b5feae7d4179e520); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b0f4074a714ea12b5feae7d4179e520)): ?>
<?php $component = $__componentOriginal8b0f4074a714ea12b5feae7d4179e520; ?>
<?php unset($__componentOriginal8b0f4074a714ea12b5feae7d4179e520); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal8b0f4074a714ea12b5feae7d4179e520 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b0f4074a714ea12b5feae7d4179e520 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.radio','data' => ['model' => 'filter.is_married','value' => '1','label' => 'Married']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.radio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => 'filter.is_married','value' => '1','label' => 'Married']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b0f4074a714ea12b5feae7d4179e520)): ?>
<?php $attributes = $__attributesOriginal8b0f4074a714ea12b5feae7d4179e520; ?>
<?php unset($__attributesOriginal8b0f4074a714ea12b5feae7d4179e520); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b0f4074a714ea12b5feae7d4179e520)): ?>
<?php $component = $__componentOriginal8b0f4074a714ea12b5feae7d4179e520; ?>
<?php unset($__componentOriginal8b0f4074a714ea12b5feae7d4179e520); ?>
<?php endif; ?>
            </div>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Nationality')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.nationality_id','model' => $this->nationalityOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Nationality')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.nationality_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->nationalityOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchNationality','wire:model.live' => 'searchNationality','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchNationality','wire:model.live' => 'searchNationality','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
             <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Born country')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.born_country_id','model' => $this->bornCountryOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Born country')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.born_country_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->bornCountryOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchPreviousNationality','wire:model.live' => 'searchPreviousNationality','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchPreviousNationality','wire:model.live' => 'searchPreviousNationality','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('City')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.born_city_id','model' => $this->cities]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('City')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.born_city_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->cities)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchCity','wire:model.live' => 'searchCity','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchCity','wire:model.live' => 'searchCity','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.pin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.pin']); ?><?php echo e(__('PIN')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.pin','wire:model.defer' => 'filter.pin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.pin','wire:model.defer' => 'filter.pin']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.rank']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.rank']); ?><?php echo e(__('Rank date')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex items-center space-x-1">
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.rank.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.rank.min']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.rank.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.rank.min']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.rank.min', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
                <span>-</span>
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.rank.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.rank.max']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.rank.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.rank.max']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.rank.max', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
            </div>
        </div>
        <div class="flex flex-col">
             <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Ranks')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.rank_id','model' => $this->rankOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Ranks')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.rank_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->rankOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchRank','wire:model.live' => 'searchRank','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchRank','wire:model.live' => 'searchRank','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.rank_name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.rank_name']); ?><?php echo e(__('Rank name')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.rank_name','wire:model.defer' => 'filter.rank_name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.rank_name','wire:model.defer' => 'filter.rank_name']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
             <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Education degree')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.education_degree_id','model' => $this->educationDegreeOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Education degree')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.education_degree_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->educationDegreeOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchEducationDegree','wire:model.live' => 'searchEducationDegree','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchEducationDegree','wire:model.live' => 'searchEducationDegree','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.specialty']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.specialty']); ?><?php echo e(__('Specialty')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.specialty','wire:model.defer' => 'filter.specialty']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.specialty','wire:model.defer' => 'filter.specialty']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
             <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Education place')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.educational_institution_id','model' => $this->institutionOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Education place')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.educational_institution_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->institutionOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchInstitution','wire:model.live' => 'searchInstitution','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchInstitution','wire:model.live' => 'searchInstitution','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
             <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Awards')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.award_id','model' => $this->awardOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Awards')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.award_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->awardOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchAward','wire:model.live' => 'searchAward','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchAward','wire:model.live' => 'searchAward','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col">
             <?php if (isset($component)) { $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.select-dropdown','data' => ['label' => ''.e(__('Punishments')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.punishment_id','model' => $this->punishmentOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.select-dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => ''.e(__('Punishments')).'','placeholder' => '---','mode' => 'gray','class' => 'w-full','wire:model.defer' => 'filter.punishment_id','model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->punishmentOptions)]); ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'searchPunishment','wire:model.live' => 'searchPunishment','@click.stop' => 'isOpen = true','xOn:input.stop' => 'null','xOn:keyup.stop' => 'null','xOn:keydown.stop' => 'null','xOn:change.stop' => 'null']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'searchPunishment','wire:model.live' => 'searchPunishment','@click.stop' => 'isOpen = true','x-on:input.stop' => 'null','x-on:keyup.stop' => 'null','x-on:keydown.stop' => 'null','x-on:change.stop' => 'null']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $attributes = $__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__attributesOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9)): ?>
<?php $component = $__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9; ?>
<?php unset($__componentOriginalb7c56d9f0bb75b99472ae2845823c8e9); ?>
<?php endif; ?>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-2 lg:grid-cols-5">
        <div class="flex flex-col">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.punishment_reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.punishment_reason']); ?><?php echo e(__('Punishment reason')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['mode' => 'gray','name' => 'filter.punishment_reason','wire:model.defer' => 'filter.punishment_reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.punishment_reason','wire:model.defer' => 'filter.punishment_reason']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.join_work_date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.join_work_date']); ?><?php echo e(__('Join work date')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex items-center space-x-1">
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.join_work_date.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.join_work_date.min']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.join_work_date.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.join_work_date.min']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.join_work_date.min', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
                <span>-</span>
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.join_work_date.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.join_work_date.max']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.join_work_date.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.join_work_date.max']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.join_work_date.max', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
            </div>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'filter.rank']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'filter.rank']); ?><?php echo e(__('Leave work date')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
            <div class="flex items-center space-x-1">
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.leave_work_date.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.leave_work_date.min']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.leave_work_date.min','format' => 'Y-MM-DD','wire:model.live' => 'filter.leave_work_date.min']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.leave_work_date.min', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
                <span>-</span>
                <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => 'filter.leave_work_date.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.leave_work_date.max']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => 'filter.leave_work_date.max','format' => 'Y-MM-DD','wire:model.live' => 'filter.leave_work_date.max']); ?>
                     <?php $__env->slot('script', null, []); ?> 
                      $el.onchange = function () {
                      window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('filter.leave_work_date.max', $el.value);
                      }
                     <?php $__env->endSlot(); ?>
                   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
            </div>
        </div>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal44a7a1d6b33cb19797fb1d49cc6fc857)): ?>
<?php $attributes = $__attributesOriginal44a7a1d6b33cb19797fb1d49cc6fc857; ?>
<?php unset($__attributesOriginal44a7a1d6b33cb19797fb1d49cc6fc857); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal44a7a1d6b33cb19797fb1d49cc6fc857)): ?>
<?php $component = $__componentOriginal44a7a1d6b33cb19797fb1d49cc6fc857; ?>
<?php unset($__componentOriginal44a7a1d6b33cb19797fb1d49cc6fc857); ?>
<?php endif; ?>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/filter/detail.blade.php ENDPATH**/ ?>