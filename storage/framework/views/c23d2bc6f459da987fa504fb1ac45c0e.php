<div class="flex flex-col" x-data="personnelManager()" x-init="init()">
    
     <?php $__env->slot('sidebar', null, []); ?> 
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('structure.sidebar');

$__html = app('livewire')->mount($__name, $__params, 'lw-371449552-0', $__slots ?? [], get_defined_vars());

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
            <?php echo $__env->make('partials.personnel.status-filters', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php echo $__env->make('partials.personnel.action-buttons', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
        
        <?php echo $__env->make('partials.personnel.position-filters', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-inherit border-b border-gray-200 shadow sm:rounded-xl">
                    <?php if (isset($component)) { $__componentOriginal3ee30789824fd1cc17cb4ff8e03df656 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ee30789824fd1cc17cb4ff8e03df656 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.tbl','data' => ['headers' => $this->getTableHeaders()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.tbl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['headers' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->getTableHeaders())]); ?>
                        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $this->personnels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $personnel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                'relative',
                                'bg-rose-100' => !empty($personnel->leave_work_date),
                                'bg-white' => $personnel->hasActiveVacation || $personnel->hasActiveBusinessTrip,
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
                                            <?php
                                                $activeVacation = $personnel->hasActiveVacation;
                                                $vacationStart = $activeVacation->start_date;
                                                $vacationEnd = $activeVacation->return_work_date;
                                            ?>
                                            <?php if (isset($component)) { $__componentOriginale375e741fa8af2e5aa10d29452e1526c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale375e741fa8af2e5aa10d29452e1526c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.progress','data' => ['startDate' => $vacationStart,'endDate' => $vacationEnd,'color' => 'emerald']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('progress'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['startDate' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($vacationStart),'endDate' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($vacationEnd),'color' => 'emerald']); ?>
                                                <?php echo e(__('In vacation')); ?>

                                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale375e741fa8af2e5aa10d29452e1526c)): ?>
<?php $attributes = $__attributesOriginale375e741fa8af2e5aa10d29452e1526c; ?>
<?php unset($__attributesOriginale375e741fa8af2e5aa10d29452e1526c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale375e741fa8af2e5aa10d29452e1526c)): ?>
<?php $component = $__componentOriginale375e741fa8af2e5aa10d29452e1526c; ?>
<?php unset($__componentOriginale375e741fa8af2e5aa10d29452e1526c); ?>
<?php endif; ?>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        <!--[if BLOCK]><![endif]--><?php if($personnel->hasActiveBusinessTrip): ?>
                                            <?php
                                                $businessTrip = $personnel->hasActiveBusinessTrip;
                                                $startDate = $businessTrip->start_date;
                                                $endDate = $businessTrip->end_date;
                                            ?>
                                            <?php if (isset($component)) { $__componentOriginale375e741fa8af2e5aa10d29452e1526c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale375e741fa8af2e5aa10d29452e1526c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.progress','data' => ['startDate' => $startDate,'endDate' => $endDate,'color' => 'rose']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('progress'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['startDate' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($startDate),'endDate' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($endDate),'color' => 'rose']); ?>
                                                <?php echo e(__('In business trip')); ?>

                                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale375e741fa8af2e5aa10d29452e1526c)): ?>
<?php $attributes = $__attributesOriginale375e741fa8af2e5aa10d29452e1526c; ?>
<?php unset($__attributesOriginale375e741fa8af2e5aa10d29452e1526c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale375e741fa8af2e5aa10d29452e1526c)): ?>
<?php $component = $__componentOriginale375e741fa8af2e5aa10d29452e1526c; ?>
<?php unset($__componentOriginale375e741fa8af2e5aa10d29452e1526c); ?>
<?php endif; ?>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    </div>

                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo e(($this->personnels->currentpage() - 1) * $this->personnels->perpage() + $key + 1); ?>

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

                                        <!--[if BLOCK]><![endif]--><?php if($personnel->is_pending): ?>
                                            <div
                                                class="text-xs font-medium rounded-lg shadow-sm px-4 py-1 flex space-x-2 items-center bg-teal-50 border border-teal-200 text-teal-500">
                                                <svg class="h-5 w-5 text-teal-500" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                <span class=""><?php echo e(__('Waiting for approval')); ?></span>
                                            </div>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                        <!--[if BLOCK]><![endif]--><?php if($status == 'deleted'): ?>
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500"><?php echo e(__('Deleted date')); ?>:</span>
                                                    <span
                                                        class="text-black"><?php echo e(\Carbon\Carbon::parse($personnel->deleted_at)->format('d.m.Y H:i')); ?></span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500"><?php echo e(__('Deleted by')); ?>:</span>
                                                    <span
                                                        class="text-black"><?php echo e($personnel->personDidDelete->name); ?></span>
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
                                            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($personnel->photo)); ?>"
                                                alt=""
                                                class="flex-none rounded-xl object-cover w-14 h-14 border-2 shadow-lg border-zinc-200">
                                        <?php else: ?>
                                            <img src="<?php echo e(asset('assets/images/no-image.png')); ?>" alt=""
                                                class="flex-none rounded-xl object-cover w-14 h-14 border-2 shadow-lg border-zinc-200">
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-sm font-medium text-zinc-900">
                                                <?php echo e($personnel->fullname); ?>

                                            </span>
                                            <span
                                                class="text-sm w-max font-medium text-neutral-600 rounded-xl px-3 py-1 shadow-sm bg-neutral-200/70">
                                                <?php echo e($personnel->gender == 1 ? __('Man') : __('Woman')); ?>

                                            </span>
                                            <!--[if BLOCK]><![endif]--><?php if(!empty($personnel->latestRank)): ?>
                                                <span
                                                    class="text-sm font-medium rounded-xl px-3 py-1 shadow-sm w-max bg-green-950 text-yellow-400">
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
                                        <span
                                            class="text-zinc-900 text-sm font-medium"><?php echo e($personnel->structure->name); ?></span>
                                        <span
                                            class="text-zinc-600 text-sm font-medium"><?php echo e($personnel->position->name); ?></span>
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
                                    <?php if (isset($component)) { $__componentOriginale5917d3361d62a806f959a2eac05071c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5917d3361d62a806f959a2eac05071c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.cell-vertical','data' => ['title' => 'Join date']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.cell-vertical'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Join date']); ?>
                                        <?php echo e(\Carbon\Carbon::parse($personnel->join_work_date)->format('d.m.Y')); ?>

                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5917d3361d62a806f959a2eac05071c)): ?>
<?php $attributes = $__attributesOriginale5917d3361d62a806f959a2eac05071c; ?>
<?php unset($__attributesOriginale5917d3361d62a806f959a2eac05071c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5917d3361d62a806f959a2eac05071c)): ?>
<?php $component = $__componentOriginale5917d3361d62a806f959a2eac05071c; ?>
<?php unset($__componentOriginale5917d3361d62a806f959a2eac05071c); ?>
<?php endif; ?>
                                    <!--[if BLOCK]><![endif]--><?php if(!empty($personnel->leave_work_date)): ?>
                                        <?php if (isset($component)) { $__componentOriginale5917d3361d62a806f959a2eac05071c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5917d3361d62a806f959a2eac05071c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.cell-vertical','data' => ['title' => 'Leave date','textColor' => 'text-rose-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.cell-vertical'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Leave date','text-color' => 'text-rose-500']); ?>
                                            <?php echo e(\Carbon\Carbon::parse($personnel->leave_work_date)->format('d.m.Y')); ?>

                                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5917d3361d62a806f959a2eac05071c)): ?>
<?php $attributes = $__attributesOriginale5917d3361d62a806f959a2eac05071c; ?>
<?php unset($__attributesOriginale5917d3361d62a806f959a2eac05071c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5917d3361d62a806f959a2eac05071c)): ?>
<?php $component = $__componentOriginale5917d3361d62a806f959a2eac05071c; ?>
<?php unset($__componentOriginale5917d3361d62a806f959a2eac05071c); ?>
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
                                    <div class="flex space-x-2 items-center">
                                        <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                                                <a href="#"
                                                    wire:click="openSideMenu('edit-personnel',<?php echo e($personnel->id); ?>)"
                                                    class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
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
                                                <button wire:click="restoreData('<?php echo e($personnel->tabel_no); ?>')"
                                                    class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
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

                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('edit-personnels')): ?>
                                            <div class="relative inline-block text-left" x-data="{ showContextMenu: false, showTooltip: '' }">
                                                <div>
                                                    <button @click="showContextMenu = !showContextMenu"
                                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100">
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
                                                    @click.outside="showContextMenu = false" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                                        'absolute right-0 z-10 mt-2 origin-bottom-right w-max rounded-md bg-white shadow-lg shadow-black/5 ring-1 ring-black ring-opacity-5 focus:outline-none',
                                                        'bottom-full' => $loop->index >= 1,
                                                        'top-full' => $loop->index < 1,
                                                    ]); ?>"
                                                    role="menu" aria-orientation="vertical" aria-labelledby="menu-button"
                                                    tabindex="-1"
                                                >
                                                    <div class="flex items-center divide-x divide-neutral-100" role="none">
                                                        <button
                                                            wire:click="openSideMenu('show-files','<?php echo e($personnel->tabel_no); ?>')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'files'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <?php if (isset($component)) { $__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.files-icon','data' => ['hover' => 'text-blue-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.files-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['hover' => 'text-blue-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9)): ?>
<?php $attributes = $__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9; ?>
<?php unset($__attributesOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9)): ?>
<?php $component = $__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9; ?>
<?php unset($__componentOriginal7dd4dad32983eebaf5b0cf2c88f3cdd9); ?>
<?php endif; ?>
                                                            <div x-show="showTooltip == 'files'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 rounded-lg border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                <?php echo e(__('Files')); ?>

                                                            </div>
                                                        </button>
                                                        <a href="<?php echo e(route('print.personnel', $personnel->id)); ?>"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium  hover:bg-slate-100"
                                                            target="_blank"
                                                            @mouseover="showTooltip = 'print'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <?php if (isset($component)) { $__componentOriginalc28559b53500c6783f3a7dc437a050ab = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc28559b53500c6783f3a7dc437a050ab = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.print-outline-icon','data' => ['hover' => 'text-blue-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.print-outline-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['hover' => 'text-blue-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc28559b53500c6783f3a7dc437a050ab)): ?>
<?php $attributes = $__attributesOriginalc28559b53500c6783f3a7dc437a050ab; ?>
<?php unset($__attributesOriginalc28559b53500c6783f3a7dc437a050ab); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc28559b53500c6783f3a7dc437a050ab)): ?>
<?php $component = $__componentOriginalc28559b53500c6783f3a7dc437a050ab; ?>
<?php unset($__componentOriginalc28559b53500c6783f3a7dc437a050ab); ?>
<?php endif; ?>
                                                            <div x-show="showTooltip == 'print'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                <?php echo e(__('Print')); ?>

                                                            </div>
                                                        </a>
                                                        <button
                                                            wire:click="openSideMenu('show-information','<?php echo e($personnel->tabel_no); ?>')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'information'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <?php if (isset($component)) { $__componentOriginal9906a361620ca30cfbf4dda88768efec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9906a361620ca30cfbf4dda88768efec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.profile-outline-icon','data' => ['hover' => 'text-blue-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.profile-outline-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['hover' => 'text-blue-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9906a361620ca30cfbf4dda88768efec)): ?>
<?php $attributes = $__attributesOriginal9906a361620ca30cfbf4dda88768efec; ?>
<?php unset($__attributesOriginal9906a361620ca30cfbf4dda88768efec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9906a361620ca30cfbf4dda88768efec)): ?>
<?php $component = $__componentOriginal9906a361620ca30cfbf4dda88768efec; ?>
<?php unset($__componentOriginal9906a361620ca30cfbf4dda88768efec); ?>
<?php endif; ?>
                                                            <div x-show="showTooltip == 'information'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                <?php echo e(__('Information')); ?>

                                                            </div>
                                                        </button>
                                                        <button
                                                            wire:click="printInfo('<?php echo e($personnel->id); ?>')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'orders'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <?php if (isset($component)) { $__componentOriginal9b80fa9788394248f2b380b2fdd350e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9b80fa9788394248f2b380b2fdd350e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.orders-icon','data' => ['hover' => 'text-blue-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.orders-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['hover' => 'text-blue-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9b80fa9788394248f2b380b2fdd350e3)): ?>
<?php $attributes = $__attributesOriginal9b80fa9788394248f2b380b2fdd350e3; ?>
<?php unset($__attributesOriginal9b80fa9788394248f2b380b2fdd350e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9b80fa9788394248f2b380b2fdd350e3)): ?>
<?php $component = $__componentOriginal9b80fa9788394248f2b380b2fdd350e3; ?>
<?php unset($__componentOriginal9b80fa9788394248f2b380b2fdd350e3); ?>
<?php endif; ?>
                                                            <div x-show="showTooltip == 'orders'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 rounded-lg border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                <?php echo e(__('Orders')); ?>

                                                            </div>
                                                        </button>
                                                        <button
                                                            wire:click="openSideMenu('show-vacations','<?php echo e($personnel->tabel_no); ?>')"
                                                            class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium relative hover:bg-slate-100"
                                                            @mouseover="showTooltip = 'vacations'"
                                                            @mouseleave="showTooltip = ''"
                                                        >
                                                            <?php if (isset($component)) { $__componentOriginal36d64938bb4d5f38af4c4cf42ab4b7c6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36d64938bb4d5f38af4c4cf42ab4b7c6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.vacation-outline-icon','data' => ['hover' => 'text-blue-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.vacation-outline-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['hover' => 'text-blue-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36d64938bb4d5f38af4c4cf42ab4b7c6)): ?>
<?php $attributes = $__attributesOriginal36d64938bb4d5f38af4c4cf42ab4b7c6; ?>
<?php unset($__attributesOriginal36d64938bb4d5f38af4c4cf42ab4b7c6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36d64938bb4d5f38af4c4cf42ab4b7c6)): ?>
<?php $component = $__componentOriginal36d64938bb4d5f38af4c4cf42ab4b7c6; ?>
<?php unset($__componentOriginal36d64938bb4d5f38af4c4cf42ab4b7c6); ?>
<?php endif; ?>
                                                            <div x-show="showTooltip == 'vacations'"
                                                                 class="absolute -top-9 left-1/2 opacity-100 -translate-x-1/2 z-10 whitespace-nowrap bg-white shadow-lg shadow-black/5 rounded-lg border border-neutral-200/70 px-2 py-1 text-center text-sm text-neutral-600 transition-all ease-out dark:bg-neutral-900/80 dark:text-neutral-50"
                                                                 role="tooltip"
                                                            >
                                                                <?php echo e(__('Vacations')); ?>

                                                            </div>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!--[if BLOCK]><![endif]--><?php if($status != 'deleted'): ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete-personnels')): ?>
                                                <button wire:click="setDeletePersonnel('<?php echo e($personnel->tabel_no); ?>')"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700">
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
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700">
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
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <?php if (isset($component)) { $__componentOriginal2d7ce9af332b48c30415ac50055c18a5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2d7ce9af332b48c30415ac50055c18a5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table.empty','data' => ['rows' => count($this->getTableHeaders())]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table.empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(count($this->getTableHeaders()))]); ?> <?php echo $__env->renderComponent(); ?>
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

    <?php echo $__env->make('partials.personnel.modals', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

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

<?php $__env->startPush('js'); ?>
    
    <script>
        function personnelManager() {
            return {
                init() {
                    this.initializePaginator();
                    this.setupLivewireHooks();
                },

                initializePaginator() {
                    const paginator = document.querySelector('span[aria-current=page]>span');
                    if (paginator) {
                        paginator.classList.add('bg-blue-50', 'text-blue-600');
                    }
                },

                setupLivewireHooks() {
                    Livewire.hook('message.processed', (message, component) => {
                        const paginator = document.querySelector('span[aria-current=page]>span');
                        const updateMethods = [
                            'gotoPage', 'previousPage', 'nextPage', 'filterSelected'
                        ];
                        const updateEvents = [
                            'openSideMenu', 'closeSideMenu', 'personnelAdded',
                            'filterResetted', 'personnelWasDeleted'
                        ];
                        const updateNames = ['search'];

                        const hasUpdate = updateMethods.includes(message.updateQueue[0]?.payload?.method) ||
                            updateEvents.includes(message.updateQueue[0]?.payload?.event) ||
                            updateNames.includes(message.updateQueue[0]?.name);

                        if (hasUpdate && paginator) {
                            paginator.classList.add('bg-green-100', 'text-green-600');
                        }
                    });
                }
            }
        }
    </script>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/personnel/all-personnel.blade.php ENDPATH**/ ?>