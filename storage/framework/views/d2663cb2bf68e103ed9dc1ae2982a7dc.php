<div
    wire:poll.10000ms='getNotificationCount'
    class="relative my-auto"
    x-data={isOpen:false}
>
    <button @click="
        isOpen=!isOpen
        if(isOpen){
            Livewire.dispatch('getNotifications')
        }
    "
            class="inline-flex justify-center w-full px-3 py-2 text-sm font-medium text-blue-500 transition duration-300 ease-in rounded-lg bg-white hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-trueGray-100 focus:ring-slate-200"
    >
        <svg  fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 font-normal text-slate-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
        </svg>
        <!--[if BLOCK]><![endif]--><?php if($notificationCount): ?>
            <div class="absolute flex items-center justify-center w-4 h-4 font-medium text-rose-500 bg-rose-200 rounded-full border-1 text-[11px] top-0 right-0">
                <?php echo e($notificationCount); ?>

            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </button>
    <div class="absolute z-40 text-left text-gray-700 bg-white shadow-2xl shadow-slate-200 border border-slate-200 -right-24 md:-right-8 w-72 md:w-96 rounded-xl"
         style="display:none;"
         x-show="isOpen"
         x-transition:enter="transition duration-200 transform ease-out"
         x-transition:enter-start="scale-75"
         x-transition:leave="transition duration-100 transform ease-in"
         x-transition:leave-end="opacity-0 scale-90"
         @click.away="isOpen = false"
         @keydown.escape.window="isOpen = false">
        <ul class="divide-y overflow-y-auto text-xs font-normal max-h-96 rounded-tl-xl rounded-tr-xl"
        >
            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php if (isset($component)) { $__componentOriginal7f7235d2088998fb10e23466b5a44ff1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f7235d2088998fb10e23466b5a44ff1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.notification.item','data' => ['notification' => $notification]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('notification.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['notification' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notification)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f7235d2088998fb10e23466b5a44ff1)): ?>
<?php $attributes = $__attributesOriginal7f7235d2088998fb10e23466b5a44ff1; ?>
<?php unset($__attributesOriginal7f7235d2088998fb10e23466b5a44ff1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f7235d2088998fb10e23466b5a44ff1)): ?>
<?php $component = $__componentOriginal7f7235d2088998fb10e23466b5a44ff1; ?>
<?php unset($__componentOriginal7f7235d2088998fb10e23466b5a44ff1); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <!--[if BLOCK]><![endif]--><?php if($isLoading): ?>
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = range(1,3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-center px-5 py-3 transition duration-150 ease-in animate-pulse">
                            <div class="w-10 h-10 bg-gray-200 rounded-xl"></div>
                            <div class="flex-1 ml-4 space-y-2">
                                <div class="w-full h-3 bg-gray-200 rounded"></div>
                                <div class="w-full h-3 bg-gray-200 rounded"></div>
                                <div class="w-1/2 h-3 bg-gray-200 rounded"></div>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                <?php else: ?>
                    <div class="w-40 py-6 mx-auto">
                        <img class="mx-auto mix-blend-luminosity" src="<?php echo e(asset('/assets/images/chat.png')); ?>" alt="">
                        <div class="mt-6 font-medium text-center text-sm text-gray-400"><?php echo e(__('No new notifications')); ?></div>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </ul>
        <div class="text-center border-t border-gray-300 flex justify-between">
            <a wire:navigate href="<?php echo e(route('notifications')); ?>" class="text-slate-600  px-5 py-3 transition duration-300 text-sm font-medium hover:text-green-400">
                <?php echo e(__('Show all notifications')); ?>

            </a>
            <button
                wire:click="markAllAsRead"
                @click="isOpen = false"
                class="appearance-none px-5 py-3 text-sm font-medium transition duration-150 ease-in hover:text-blue-500"
            >
                <?php echo e(__('Mark all as read')); ?>

            </button>
        </div>
    </div >

</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/notification/notifications.blade.php ENDPATH**/ ?>