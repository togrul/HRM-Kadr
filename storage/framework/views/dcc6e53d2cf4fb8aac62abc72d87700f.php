<div class="flex flex-col"
    x-data
    x-init="
    paginator = document.querySelector('span[aria-current=page]>span');
    if(paginator != null)
    {
        paginator.classList.add('bg-blue-50','text-blue-600')
    }
    Livewire.hook('message.processed', (message,component) => {
        const paginator = document.querySelector('span[aria-current=page]>span')
        if(
            ['gotoPage','previousPage','nextPage','filterSelected'].includes(message.updateQueue[0].payload.method)
            || ['openSideMenu','closeSideMenu','personnelAdded','filterResetted','personnelWasDeleted'].includes(message.updateQueue[0].payload.event)
            || ['search'].includes(message.updateQueue[0].name)
        ){
            if(paginator != null)
            {
                paginator.classList.add('bg-green-100','text-green-600')
            }
        }
    })
">
    
     <?php $__env->slot('sidebar', null, []); ?> 
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('structure.sidebar');

$__html = app('livewire')->mount($__name, $__params, 'lw-3935985241-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
     <?php $__env->endSlot(); ?>
    

    <div class="flex flex-col space-y-4 px-6 py-4">

        <div class="flex justify-between items-center">
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
                <?php if (isset($component)) { $__componentOriginal6307c0378087124f8a5ba7af51640019 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6307c0378087124f8a5ba7af51640019 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.nav','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'current\')','active' => $status === 'current']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'current\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'current')]); ?>
                        <?php echo e(__('Active')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'leaves\')','active' => $status === 'leaves']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'leaves\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'leaves')]); ?>
                        <?php echo e(__('Resigned')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'all\')','active' => $status === 'all']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'all\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'all')]); ?>
                        <?php echo e(__('All')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    
                    
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'deleted\')','active' => $status === 'deleted']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'deleted\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'deleted')]); ?>
                        <?php echo e(__('Deleted')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'pending\')','active' => $status === 'pending']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'pending\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'pending')]); ?>
                        <?php echo e(__('Pending')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $attributes = $__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__attributesOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47)): ?>
<?php $component = $__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47; ?>
<?php unset($__componentOriginal5c1e1dd95975c2ff879ca8863e56fe47); ?>
<?php endif; ?>
                    
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6307c0378087124f8a5ba7af51640019)): ?>
<?php $attributes = $__attributesOriginal6307c0378087124f8a5ba7af51640019; ?>
<?php unset($__attributesOriginal6307c0378087124f8a5ba7af51640019); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6307c0378087124f8a5ba7af51640019)): ?>
<?php $component = $__componentOriginal6307c0378087124f8a5ba7af51640019; ?>
<?php unset($__componentOriginal6307c0378087124f8a5ba7af51640019); ?>
<?php endif; ?>
            </div>
            <div class="flex flex-col">
                <div class="flex space-x-4">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('add-personnels')): ?>
                    <button wire:click="openSideMenu('add-personnel')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50" type="button">
                        <?php if (isset($component)) { $__componentOriginal7444528437396d3a60bf0a6dc6700f0a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7444528437396d3a60bf0a6dc6700f0a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.add-file','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.add-file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7444528437396d3a60bf0a6dc6700f0a)): ?>
<?php $attributes = $__attributesOriginal7444528437396d3a60bf0a6dc6700f0a; ?>
<?php unset($__attributesOriginal7444528437396d3a60bf0a6dc6700f0a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7444528437396d3a60bf0a6dc6700f0a)): ?>
<?php $component = $__componentOriginal7444528437396d3a60bf0a6dc6700f0a; ?>
<?php unset($__componentOriginal7444528437396d3a60bf0a6dc6700f0a); ?>
<?php endif; ?>
                    </button>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('export-personnels')): ?>
                    <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-7 h-7 fill-green-400 transition-all duration-300 hover:fill-green-500">
                            <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                        </svg>
                    </button>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                    <button wire:click.prevent="printPage('personnel')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50" type="button">
                        <?php if (isset($component)) { $__componentOriginal5a7650ea88ba93c672a6a4c1810416c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5a7650ea88ba93c672a6a4c1810416c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.print-file','data' => ['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-8 h-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.print-file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-8 h-8']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5a7650ea88ba93c672a6a4c1810416c3)): ?>
<?php $attributes = $__attributesOriginal5a7650ea88ba93c672a6a4c1810416c3; ?>
<?php unset($__attributesOriginal5a7650ea88ba93c672a6a4c1810416c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5a7650ea88ba93c672a6a4c1810416c3)): ?>
<?php $component = $__componentOriginal5a7650ea88ba93c672a6a4c1810416c3; ?>
<?php unset($__componentOriginal5a7650ea88ba93c672a6a4c1810416c3); ?>
<?php endif; ?>
                    </button>
                    <?php endif; ?>
                    <button
                       @click="
                            $wire.dispatch('setOpenFilter');
                        "
                         class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'flex relative items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-gray-100',
                            'bg-gray-100' => count($filters) > 0
                         ]); ?>" type="button">
                            <?php if (isset($component)) { $__componentOriginal01046fb947b9b5b0a1a7f166baac84a0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal01046fb947b9b5b0a1a7f166baac84a0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.search-file','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.search-file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
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
                </div>
                <!--[if BLOCK]><![endif]--><?php if(count($filters) > 0): ?>
                <button wire:click="resetSelectedFilter" class="appearance-none text-rose-500 text-sm font-medium flex items-center space-x-2 justify-end">
                    <?php if (isset($component)) { $__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.remove-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.remove-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc)): ?>
<?php $attributes = $__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc; ?>
<?php unset($__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc)): ?>
<?php $component = $__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc; ?>
<?php unset($__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc); ?>
<?php endif; ?>
                   <span> <?php echo e(__('Reset filter')); ?></span>
                </button>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </div>

        
        <div class="flex justify-start items-center flex-wrap gap-2">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button
                    wire:click.prevent="setPosition(<?php echo e($position->id); ?>)"
                    class="appearance-none w-max text-sm font-medium bg-gray-50 shadow-md text-gray-600 border rounded-md px-3 py-1 transition-all duration-300 hover:shadow-sm hover:text-gray-900"
                >
                   <span> <?php echo e($position->name); ?> </span>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            <!--[if BLOCK]><![endif]--><?php if(!empty($selectedPosition)): ?>
                    <button
                        wire:click.prevent="resetFilter"
                        class="appearance-none w-max text-sm font-medium bg-slate-100 text-rose-500 rounded-2xl px-3 py-1 transition-all duration-300 hover:bg-slate-200"
                    >
                        <?php echo e(__('Reset')); ?>

                    </button>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                <?php if (isset($component)) { $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => [__('#'),__('Tabel'),__('Fullname'),__('Position'),'action','action','action']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([__('#'),__('Tabel'),__('Fullname'),__('Position'),'action','action','action'])]); ?>
                    <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $this->personnels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $personnel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'relative',
                        'bg-white' => empty($personnel->leave_work_date),
                        'bg-red-100' => !empty($personnel->leave_work_date)
                    ]); ?>">
                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                            <div class="flex flex-col justify-between h-full absolute top-0 left-0">
                                <!--[if BLOCK]><![endif]--><?php if($personnel->hasActiveVacation): ?>
                                    <span class=" text-green-50 flex justify-center items-center text-sm font-medium bg-green-600 px-2 py-1 rounded-sm">
                                        <?php echo e(__('In vacation')); ?>

                                    </span>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                <!--[if BLOCK]><![endif]--><?php if($personnel->hasActiveBusinessTrip): ?>
                                    <span class=" text-rose-50 flex justify-center items-center text-sm font-medium bg-rose-600 px-2 py-1 rounded-sm">
                                        <?php echo e(__('In business trip')); ?>

                                    </span>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>

                            <span class="text-sm font-medium text-gray-700">
                                <?php echo e(($this->personnels->currentpage()-1) * $this->personnels->perpage() + $key + 1); ?>

                           </span>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                            <div class="flex flex-col space-y-1">
                                <span class="text-sm font-medium text-blue-500">
                                    <?php echo e($personnel->tabel_no); ?>

                               </span>
                                <!--[if BLOCK]><![endif]--><?php if($status == 'deleted'): ?>
                                <div class="flex flex-col text-xs font-medium">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500"><?php echo e(__('Deleted date')); ?>:</span>
                                        <span class="text-black"><?php echo e(\Carbon\Carbon::parse($personnel->deleted_at)->format('d.m.Y H:i')); ?></span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500"><?php echo e(__('Deleted by')); ?>:</span>
                                        <span class="text-black"><?php echo e($personnel->personDidDelete->name); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                            <div class="flex items-center space-x-2 px-2">
                                <!--[if BLOCK]><![endif]--><?php if(!empty($personnel->photo)): ?>
                                    <img src="<?php echo e(asset('/storage/'.$personnel->photo)); ?>" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                <?php else: ?>
                                    <img src="<?php echo e(asset('assets/images/no-image.png')); ?>" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                               <div class="flex flex-col space-y-1">
                                <span class="text-sm font-medium text-gray-600">
                                    <?php echo e($personnel->fullname); ?>

                               </span>
                               <span class="text-sm w-max font-medium text-gray-600 rounded-xl px-3 py-1 shadow-sm bg-gray-100">
                                    <?php echo e($personnel->gender == 1 ? __('Man') : __('Woman')); ?>

                               </span>
                                <!--[if BLOCK]><![endif]--><?php if(!empty($personnel->latestRank)): ?>
                                <span class="text-sm font-medium rounded-xl px-3 py-1 shadow-sm w-max bg-green-950 text-yellow-400">
                                    <?php echo e($personnel->latestRank?->rank->name); ?>

                               </span>
                               <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                               </div>
                            </div>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                            <div class="flex flex-col space-y-1">
                                <div class="flex space-x-1 items-center">
                                    <span class="text-gray-500 text-sm font-medium"><?php echo e(__('Structure')); ?>:</span>
                                    <span class="text-gray-900 text-sm font-medium bg-green-100 px-2 py-1 rounded-lg"><?php echo e($personnel->structure->name); ?></span>
                                </div>
                                <div class="flex space-x-1 items-center">
                                    <span class="text-gray-500 text-sm font-medium"><?php echo e(__('Position')); ?>:</span>
                                    <span class="text-gray-900 text-sm font-medium bg-orange-100 px-2 py-1 rounded-lg"><?php echo e($personnel->position->name); ?></span>
                                </div>
                                <div class="flex space-x-1">
                                    <span class="text-gray-500 text-sm font-medium"><?php echo e(__('Join date')); ?>:</span>
                                    <span class="text-gray-900 text-sm font-medium"><?php echo e(\Carbon\Carbon::parse($personnel->join_work_date)->format('d.m.Y')); ?></span>
                                </div>
                                <!--[if BLOCK]><![endif]--><?php if( !empty($personnel->leave_work_date)): ?>
                                <div class="flex space-x-1">
                                    <span class="text-gray-500 text-sm font-medium"><?php echo e(__('Leave date')); ?>:</span>
                                    <span class="text-red-500 text-sm font-medium"><?php echo e(\Carbon\Carbon::parse($personnel->leave_work_date)->format('d.m.Y')); ?></span>
                                </div>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => ['isButton' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isButton' => true]); ?>
                            <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                             <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                                <a href="#" wire:click="openSideMenu('edit-personnel',<?php echo e($personnel->id); ?>)" class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                    <?php if (isset($component)) { $__componentOriginal1ac9cc6c9f431e28031aa48530f41a62 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ac9cc6c9f431e28031aa48530f41a62 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.profile-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.profile-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ac9cc6c9f431e28031aa48530f41a62)): ?>
<?php $attributes = $__attributesOriginal1ac9cc6c9f431e28031aa48530f41a62; ?>
<?php unset($__attributesOriginal1ac9cc6c9f431e28031aa48530f41a62); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ac9cc6c9f431e28031aa48530f41a62)): ?>
<?php $component = $__componentOriginal1ac9cc6c9f431e28031aa48530f41a62; ?>
<?php unset($__componentOriginal1ac9cc6c9f431e28031aa48530f41a62); ?>
<?php endif; ?>
                                </a>
                             <?php endif; ?>
                            <?php else: ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                                <button
                                    wire:click="restoreData('<?php echo e($personnel->tabel_no); ?>')"
                                    class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-teal-50 hover:bg-teal-100 hover:text-gray-700"
                                >
                                    <?php if (isset($component)) { $__componentOriginal3b29863ca6c763a42daeb7da6d628a8a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.recover','data' => ['color' => 'text-teal-500','hover' => 'text-teal-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.recover'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-teal-500','hover' => 'text-teal-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a)): ?>
<?php $attributes = $__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a; ?>
<?php unset($__attributesOriginal3b29863ca6c763a42daeb7da6d628a8a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b29863ca6c763a42daeb7da6d628a8a)): ?>
<?php $component = $__componentOriginal3b29863ca6c763a42daeb7da6d628a8a; ?>
<?php unset($__componentOriginal3b29863ca6c763a42daeb7da6d628a8a); ?>
<?php endif; ?>
                                </button>
                                <?php endif; ?>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => ['isButton' => true,'style' => 'text-align: center !important;']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isButton' => true,'style' => 'text-align: center !important;']); ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                            <div class="relative inline-block text-left" x-data="{showContextMenu:false}">
                                <div>
                                    <button @click="showContextMenu = !showContextMenu" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100">
                                        <?php if (isset($component)) { $__componentOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.settings-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.settings-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01)): ?>
<?php $attributes = $__attributesOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01; ?>
<?php unset($__attributesOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01)): ?>
<?php $component = $__componentOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01; ?>
<?php unset($__componentOriginal2fcb1b3aaae59cbabe31af4a9ea1ca01); ?>
<?php endif; ?>
                                    </button>
                                </div>
                                <div x-show="showContextMenu"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-90"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-90"
                                     @click.outside="showContextMenu = false"
                                     class="absolute right-0 z-10 mt-2 w-max origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                    <div class="flex flex-col" role="none">
                                        <button wire:click="openSideMenu('show-files','<?php echo e($personnel->tabel_no); ?>')"
                                                class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                        >
                                            <span class="text-slate-500"><?php echo e(__('Files')); ?></span>
                                        </button>
                                        <a href="<?php echo e(route('print.personnel',$personnel->id)); ?>"
                                           class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                           target="_blank"
                                        >
                                            <span class="text-slate-500"><?php echo e(__('Print')); ?></span>
                                        </a>
                                        <button wire:click="printInfo('<?php echo e($personnel->id); ?>')"
                                                class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                        >
                                            <span class="text-slate-500"><?php echo e(__('Orders')); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>

                        <?php if (isset($component)) { $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.td','data' => ['isButton' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.td'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isButton' => true]); ?>
                        <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                             <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete-personnels')): ?>
                            <button
                                wire:click="setDeletePersonnel('<?php echo e($personnel->tabel_no); ?>')"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <?php if (isset($component)) { $__componentOriginal795db0355ab159c86fb4ade6f5b93d10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal795db0355ab159c86fb4ade6f5b93d10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.delete-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.delete-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal795db0355ab159c86fb4ade6f5b93d10)): ?>
<?php $attributes = $__attributesOriginal795db0355ab159c86fb4ade6f5b93d10; ?>
<?php unset($__attributesOriginal795db0355ab159c86fb4ade6f5b93d10); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal795db0355ab159c86fb4ade6f5b93d10)): ?>
<?php $component = $__componentOriginal795db0355ab159c86fb4ade6f5b93d10; ?>
<?php unset($__componentOriginal795db0355ab159c86fb4ade6f5b93d10); ?>
<?php endif; ?>
                            </button>
                             <?php endif; ?>
                         <?php else: ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                                 <button
                                    wire:confirm="<?php echo e(__('Are you sure you want to remove this data?')); ?>"
                                    wire:click="forceDeleteData('<?php echo e($personnel->tabel_no); ?>')"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                 >
                                     <?php if (isset($component)) { $__componentOriginalc68e9de97adc0f3f617404bb2241d8ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.force-delete','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.force-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad)): ?>
<?php $attributes = $__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad; ?>
<?php unset($__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc68e9de97adc0f3f617404bb2241d8ad)): ?>
<?php $component = $__componentOriginalc68e9de97adc0f3f617404bb2241d8ad; ?>
<?php unset($__componentOriginalc68e9de97adc0f3f617404bb2241d8ad); ?>
<?php endif; ?>
                                </button>
                             <?php endif; ?>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $attributes = $__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__attributesOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b)): ?>
<?php $component = $__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b; ?>
<?php unset($__componentOriginalc91c98e046a1434e6f8cdd0cdedd160b); ?>
<?php endif; ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7">
                           <div class="flex justify-center items-center py-4">
                            <span class="font-medium"><?php echo e(__('No information added')); ?></span>
                           </div>
                        </td>
                    </tr>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656)): ?>
<?php $attributes = $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656; ?>
<?php unset($__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3ee30789824fd1cc17cb4ff8e03df656)): ?>
<?php $component = $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656; ?>
<?php unset($__componentOriginal3ee30789824fd1cc17cb4ff8e03df656); ?>
<?php endif; ?>

            </div>
            </div>
            </div>

            <div class="mt-2">
                <?php echo e($this->personnels->links()); ?>

            </div>
    </div>

    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('filter.detail',['lazy' => 'on-load']);

$__html = app('livewire')->mount($__name, $__params, 'lw-3935985241-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

    <?php if (isset($component)) { $__componentOriginal06466d70a5df71623dc2a561e77c49ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal06466d70a5df71623dc2a561e77c49ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.side-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('side-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('add-personnels')): ?>
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'add-personnel'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('personnel.add-personnel', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-3935985241-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'edit-personnel'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('personnel.edit-personnel', ['personnelModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3935985241-3', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'show-files'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('personnel.files', ['personnelModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3935985241-4', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal06466d70a5df71623dc2a561e77c49ee)): ?>
<?php $attributes = $__attributesOriginal06466d70a5df71623dc2a561e77c49ee; ?>
<?php unset($__attributesOriginal06466d70a5df71623dc2a561e77c49ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal06466d70a5df71623dc2a561e77c49ee)): ?>
<?php $component = $__componentOriginal06466d70a5df71623dc2a561e77c49ee; ?>
<?php unset($__componentOriginal06466d70a5df71623dc2a561e77c49ee); ?>
<?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete-personnels')): ?>
       <div>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('personnel.delete-personnel', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-3935985241-5', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
       </div>
    <?php endif; ?>

   <?php if (isset($component)) { $__componentOriginal2686ed4927c64f67d2844e9b73af898c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2686ed4927c64f67d2844e9b73af898c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.datepicker','data' => ['auto' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('datepicker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['auto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2686ed4927c64f67d2844e9b73af898c)): ?>
<?php $attributes = $__attributesOriginal2686ed4927c64f67d2844e9b73af898c; ?>
<?php unset($__attributesOriginal2686ed4927c64f67d2844e9b73af898c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2686ed4927c64f67d2844e9b73af898c)): ?>
<?php $component = $__componentOriginal2686ed4927c64f67d2844e9b73af898c; ?>
<?php unset($__componentOriginal2686ed4927c64f67d2844e9b73af898c); ?>
<?php endif; ?>
</div>

<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/personnel/all-personnel.blade.php ENDPATH**/ ?>