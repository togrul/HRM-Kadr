<div class="flex flex-col space-y-8"
     x-data
>
    <div class="flex items-center justify-between space-x-2 action-section py-2">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.filter.item','data' => ['wire:click.prevent' => 'setStatus(\'active\')','active' => $status === 'active']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('filter.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click.prevent' => 'setStatus(\'active\')','active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($status === 'active')]); ?>
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
        
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['mode' => 'primary','wire:click' => 'openSideMenu(\'add-template\')','class' => 'space-x-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'primary','wire:click' => 'openSideMenu(\'add-template\')','class' => 'space-x-2']); ?>
            <?php if (isset($component)) { $__componentOriginaldfc7e290c37ee4892c2a2155433789a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldfc7e290c37ee4892c2a2155433789a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.add-icon','data' => ['color' => 'text-white','hover' => 'text-gray-50']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.add-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-white','hover' => 'text-gray-50']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldfc7e290c37ee4892c2a2155433789a2)): ?>
<?php $attributes = $__attributesOriginaldfc7e290c37ee4892c2a2155433789a2; ?>
<?php unset($__attributesOriginaldfc7e290c37ee4892c2a2155433789a2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldfc7e290c37ee4892c2a2155433789a2)): ?>
<?php $component = $__componentOriginaldfc7e290c37ee4892c2a2155433789a2; ?>
<?php unset($__componentOriginaldfc7e290c37ee4892c2a2155433789a2); ?>
<?php endif; ?>
            <span><?php echo e(__('Add template')); ?></span>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
        
    </div>

    <div class="flex flex-col space-y-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mt-4">
            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex flex-col space-y-2 bg-slate-50 border border-slate-100 shadow-sm rounded-lg w-full px-3 py-4">
                    <div class="flex flex-col space-y-2 items-center justify-center">
                        <h1 class="font-medium text-sm text-center bg-blue-100 rounded-xl px-3 py-1 flex justify-center items-center text-blue-500">
                            <?php echo e($template->category->{"name_".config('app.locale')}); ?>

                        </h1>
                        <h2 class="font-medium text-base text-slate-900">
                            <?php echo e($template->name); ?>

                        </h2>
                    </div>
                    <div class="flex justify-between items-center border-t border-dashed border-slate-300 py-2">
                        <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                        
                            <a href="javascript:void(0)" wire:click.prevent="openSideMenu('edit-template',<?php echo e($template->id); ?>)" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-200 hover:text-gray-700">
                                <?php if (isset($component)) { $__componentOriginal308d511ba9bedd167c92178534240350 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal308d511ba9bedd167c92178534240350 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.edit-icon','data' => ['color' => 'text-slate-400','hover' => 'text-slate-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.edit-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-slate-400','hover' => 'text-slate-500']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal308d511ba9bedd167c92178534240350)): ?>
<?php $attributes = $__attributesOriginal308d511ba9bedd167c92178534240350; ?>
<?php unset($__attributesOriginal308d511ba9bedd167c92178534240350); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal308d511ba9bedd167c92178534240350)): ?>
<?php $component = $__componentOriginal308d511ba9bedd167c92178534240350; ?>
<?php unset($__componentOriginal308d511ba9bedd167c92178534240350); ?>
<?php endif; ?>
                            </a>
                            <button
                                wire:click.prevent = "openSideMenu('set-type',<?php echo e($template->id); ?>)"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-emerald-100 hover:text-gray-700"
                            >
                                <?php if (isset($component)) { $__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.components-icon','data' => ['color' => 'text-emerald-500','hover' => 'text-emerald-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.components-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-emerald-500','hover' => 'text-emerald-600']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd)): ?>
<?php $attributes = $__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd; ?>
<?php unset($__attributesOriginal787f4308f721dc1f02ce5f8211e5e0bd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd)): ?>
<?php $component = $__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd; ?>
<?php unset($__componentOriginal787f4308f721dc1f02ce5f8211e5e0bd); ?>
<?php endif; ?>
                            </button>
                            <button
                                wire:click.prevent = "setDeleteTemplate(<?php echo e($template->id); ?>)"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <?php if (isset($component)) { $__componentOriginal795db0355ab159c86fb4ade6f5b93d10 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal795db0355ab159c86fb4ade6f5b93d10 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.delete-icon','data' => ['color' => 'text-rose-500','hover' => 'text-rose-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.delete-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-500','hover' => 'text-rose-600']); ?> <?php echo $__env->renderComponent(); ?>
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
                        
                        <?php else: ?>
                            <button
                                wire:click="restoreData(<?php echo e($template->id); ?>)"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
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
                            <button
                                wire:confirm="<?php echo e(__('Are you sure you want to remove this data?')); ?>"
                                wire:click="forceDeleteData(<?php echo e($template->id); ?>)"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                <?php if (isset($component)) { $__componentOriginalc68e9de97adc0f3f617404bb2241d8ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc68e9de97adc0f3f617404bb2241d8ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.force-delete','data' => ['color' => 'text-rose-400','hover' => 'text-rose-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.force-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-400','hover' => 'text-rose-500']); ?> <?php echo $__env->renderComponent(); ?>
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
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="flex flex-col space-y-2 justify-center items-center py-8 sm:col-span-2 md:col-span-3 lg:col-span-4 bg-slate-50">
                    <svg class="w-12 h-12 text-slate-400 drop-shadow-lg" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"></path>
                    </svg>
                    <span class="font-medium drop-shadow-lg"><?php echo e(__('No information added')); ?></span>
                </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        </div>
        <div>
            <?php echo e($templates->links()); ?>

        </div>
    </div>


    
    <div>
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
                <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'add-template'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('orders.templates.add-template', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-3506094614-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'edit-template'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('orders.templates.edit-template', ['templateModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3506094614-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'set-type'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('orders.templates.set-type', ['templateModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-3506094614-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
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
    </div>
    

    <div class="">
        <!--[if BLOCK]><![endif]--><?php if(auth()->guard()->check()): ?>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('orders.templates.delete-template');

$__html = app('livewire')->mount($__name, $__params, 'lw-3506094614-3', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/orders/templates/all-templates.blade.php ENDPATH**/ ?>