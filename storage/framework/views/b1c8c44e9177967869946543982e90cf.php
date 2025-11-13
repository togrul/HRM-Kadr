<div class="flex flex-col" x-data x-init="paginator = document.querySelector('span[aria-current=page]>span');
if (paginator != null) {
    paginator.classList.add('bg-blue-50', 'text-blue-600')
}
Livewire.hook('message.processed', (message, component) => {
    const paginator = document.querySelector('span[aria-current=page]>span')
    if (
        ['gotoPage', 'previousPage', 'nextPage', 'resetFilter'].includes(message.updateQueue[0].payload.method) || ['openSideMenu', 'closeSideMenu', 'staffAdded', 'staffWasDeleted'].includes(message.updateQueue[0].payload.event)
    ) {
        if (paginator != null) {
            paginator.classList.add('bg-green-100', 'text-green-600')
        }
    }
})">
    
     <?php $__env->slot('sidebar', null, []); ?> 
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('structure.sidebar');

$__html = app('livewire')->mount($__name, $__params, 'lw-1934615238-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
     <?php $__env->endSlot(); ?>
    

    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 px-6 py-4">
        <div class="flex flex-col space-y-1 pl-3">
            <!--[if BLOCK]><![endif]--><?php if($selectedPage == 'all'): ?>
                <button wire:click="showPage('vacancies')"
                    class="flex items-center justify-center shadow-sm transition-all duration-300 rounded-lg bg-slate-900 text-white hover:bg-slate-200 hover:text-slate-900 space-x-2 px-4 py-2"
                    type="button">
                    <span><?php echo e(__('Get all vacancies')); ?></span>
                </button>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <!--[if BLOCK]><![endif]--><?php if($selectedPage == 'vacancies'): ?>
                <button wire:click="showPage('all')"
                    class="flex items-center justify-center shadow-sm transition-all duration-300 rounded-xl bg-slate-900 text-white hover:bg-slate-200 hover:text-slate-900 space-x-2 px-4 py-2"
                    type="button">
                    <span><?php echo e(__('All data')); ?></span>
                </button>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        <div class="flex justify-end items-center space-x-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('add-staff')): ?>
                <button wire:click="openSideMenu('add-staff')"
                    class="flex items-center justify-center transition-all duration-300 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 space-x-2 p-2"
                    type="button">
                    <?php echo $__env->make('components.icons.add-icon', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('export-staff')): ?>
                <button
                    class="flex items-center justify-center transition-all duration-300 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100 space-x-2 p-2"
                    type="button">
                    <?php echo $__env->make('components.icons.print-file', [
                        'color' => 'text-rose-400',
                        'hover' => 'text-rose-500',
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('export-staff')): ?>
                <!--[if BLOCK]><![endif]--><?php if($selectedPage == 'vacancies'): ?>
                    <button wire:click.prevent="exportExcel"
                        class="flex items-center justify-center rounded-xl transition-all duration-300 bg-green-50 text-green-500 hover:bg-green-100 space-x-2 p-2"
                        type="button">
                        <?php if (isset($component)) { $__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.excel-icon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.excel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28)): ?>
<?php $attributes = $__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28; ?>
<?php unset($__attributesOriginalaac97465df15b1f1f8ecebffe4ef4e28); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28)): ?>
<?php $component = $__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28; ?>
<?php unset($__componentOriginalaac97465df15b1f1f8ecebffe4ef4e28); ?>
<?php endif; ?>
                    </button>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <?php endif; ?>
        </div>
    </div>

    <!--[if BLOCK]><![endif]--><?php if($selectedPage == 'all'): ?>
        <div class="flex flex-col space-y-4 px-4 mt-4">
            <!--[if BLOCK]><![endif]--><?php if($staffs->isNotEmpty()): ?>
                <div class="grid grid-cols-1 gap-3">
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $staffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $str => $stf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $structure = $stf[0]->structure;
                            $hasParent = !empty($structure->parent_id);
                            $total_sum = $stf->sum('total');
                            $total_filled = $stf->sum('filled');
                            $total_vacant = $stf->sum('vacant');
                        ?>
                        <?php if (isset($component)) { $__componentOriginal6ae67d1df8a58409bd750877aad31bdd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6ae67d1df8a58409bd750877aad31bdd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.staff.root','data' => ['title' => $str,'structureId' => $stf[0]->structure_id,'hasParent' => $hasParent,'totalSum' => $total_sum,'totalFilled' => $total_filled,'totalVacant' => $total_vacant]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('staff.root'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($str),'structureId' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stf[0]->structure_id),'hasParent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasParent),'total_sum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($total_sum),'total_filled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($total_filled),'total_vacant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($total_vacant)]); ?>
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $stf; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if (isset($component)) { $__componentOriginal5879aa5feceb7646a0beba77af61bc50 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5879aa5feceb7646a0beba77af61bc50 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.staff.item','data' => ['hasParent' => $hasParent,'model' => $st]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('staff.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['hasParent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasParent),'model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($st)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5879aa5feceb7646a0beba77af61bc50)): ?>
<?php $attributes = $__attributesOriginal5879aa5feceb7646a0beba77af61bc50; ?>
<?php unset($__attributesOriginal5879aa5feceb7646a0beba77af61bc50); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5879aa5feceb7646a0beba77af61bc50)): ?>
<?php $component = $__componentOriginal5879aa5feceb7646a0beba77af61bc50; ?>
<?php unset($__componentOriginal5879aa5feceb7646a0beba77af61bc50); ?>
<?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6ae67d1df8a58409bd750877aad31bdd)): ?>
<?php $attributes = $__attributesOriginal6ae67d1df8a58409bd750877aad31bdd; ?>
<?php unset($__attributesOriginal6ae67d1df8a58409bd750877aad31bdd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6ae67d1df8a58409bd750877aad31bdd)): ?>
<?php $component = $__componentOriginal6ae67d1df8a58409bd750877aad31bdd; ?>
<?php unset($__componentOriginal6ae67d1df8a58409bd750877aad31bdd); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginal2d7ce9af332b48c30415ac50055c18a5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7ce9af332b48c30415ac50055c18a5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.empty','data' => ['rows' => 5]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 5]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2d7ce9af332b48c30415ac50055c18a5)): ?>
<?php $attributes = $__attributesOriginal2d7ce9af332b48c30415ac50055c18a5; ?>
<?php unset($__attributesOriginal2d7ce9af332b48c30415ac50055c18a5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2d7ce9af332b48c30415ac50055c18a5)): ?>
<?php $component = $__componentOriginal2d7ce9af332b48c30415ac50055c18a5; ?>
<?php unset($__componentOriginal2d7ce9af332b48c30415ac50055c18a5); ?>
<?php endif; ?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <!--[if BLOCK]><![endif]--><?php if($selectedPage == 'vacancies'): ?>
        <div class="flex flex-col space-y-2 px-6">
            <div class="flex space-x-4 items-center">
                <div class="flex space-x-2 items-center">
                    <span class="text-gray-500 font-medium"><?php echo e(__('Count')); ?>:</span>
                    <span><?php echo e($staffs->count()); ?></span>
                </div>
                <div class="flex space-x-2 items-center">
                    <span class="text-gray-500 font-medium"><?php echo e(__('Total')); ?>:</span>
                    <span><?php echo e($staffs->sum('vacant')); ?></span>
                </div>
            </div>

            <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                        <?php if (isset($component)) { $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => [__('#'), __('Structure'), __('Position'), __('Vacant')]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([__('#'), __('Structure'), __('Position'), __('Vacant')])]); ?>
                            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $staffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
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
                                        <span class="text-sm font-medium">
                                            <?php echo e($loop->iteration); ?>

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
                                        <span class="text-sm font-medium">
                                            <?php echo e($staff->structure->name); ?>

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
                                        <span class="text-sm font-medium">
                                            <?php echo e($staff->position->name); ?>

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
                                        <span class="text-sm font-normal text-gray-700">
                                            <?php echo e($staff->vacant); ?>

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
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
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
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

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
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('add-staff')): ?>
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'add-staff'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('staff-schedule.add-staff', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-1934615238-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-staff')): ?>
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'edit-staff'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('staff-schedule.edit-staff', ['staffModel' => $modelName]);

$__html = app('livewire')->mount($__name, $__params, 'lw-1934615238-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('show-staff')): ?>
            <!--[if BLOCK]><![endif]--><?php if($showSideMenu == 'show-staff'): ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('staff-schedule.show-staff', ['structureModel' => $modelName,'positionModel' => $secondModel]);

$__html = app('livewire')->mount($__name, $__params, 'lw-1934615238-3', $__slots ?? [], get_defined_vars());

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
    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete-staff')): ?>
        <div>
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('staff-schedule.delete-staff', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-1934615238-4', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/staff-schedule/staffs.blade.php ENDPATH**/ ?>