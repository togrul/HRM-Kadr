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
                ['gotoPage','previousPage','nextPage','resetFilter'].includes(message.updateQueue[0].payload.method)
                || ['openSideMenu','closeSideMenu','notificationsDeleted'].includes(message.updateQueue[0].payload.event)
                || ['search'].includes(message.updateQueue[0].name)
            ){
                if(paginator != null)
                {
                    paginator.classList.add('bg-blue-50','text-blue-600')
                }
            }
})
">
    <div class="flex justify-between items-center px-8 py-4">
        <span class="font-medium text-slate-600"><?php echo e(__('Count')); ?>: <?php echo e($notifications->total()); ?></span>
        <button wire:click.prevent="clearNotifications" class="appearance-none font-medium space-x-2 flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark-x w-6 h-6 text-rose-500"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/><path d="m14.5 7.5-5 5"/><path d="m9.5 7.5 5 5"/></svg>
            <span class="text-rose-500"><?php echo e(__('Clear all notifications')); ?></span>
        </button>
    </div>

    <div class="flex flex-col">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (isset($component)) { $__componentOriginal0c87c31ede2878261957218384ed4f8a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c87c31ede2878261957218384ed4f8a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.notification.list-item','data' => ['notification' => $notification]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('notification.list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['notification' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notification)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c87c31ede2878261957218384ed4f8a)): ?>
<?php $attributes = $__attributesOriginal0c87c31ede2878261957218384ed4f8a; ?>
<?php unset($__attributesOriginal0c87c31ede2878261957218384ed4f8a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c87c31ede2878261957218384ed4f8a)): ?>
<?php $component = $__componentOriginal0c87c31ede2878261957218384ed4f8a; ?>
<?php unset($__componentOriginal0c87c31ede2878261957218384ed4f8a); ?>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        <div>
            <?php echo e($notifications->links()); ?>

        </div>
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/notification/notification-list.blade.php ENDPATH**/ ?>