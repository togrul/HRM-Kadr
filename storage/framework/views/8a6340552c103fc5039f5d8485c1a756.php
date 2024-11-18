<?php echo $__env->make('layouts.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php echo $__env->make('includes.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<!-- Page Content -->
<main x-data="{collapsed: <?php echo e(isset($sidebar) ? 'false' : 'true'); ?>}" class="mt-4 max-w-7xl mx-auto lg:px-0 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg grid grid-cols-1 md:grid-cols-3 divide-y divide-x-0 md:divide-x md:divide-y-0 divide-gray-200">
    <?php if(isset($sidebar)): ?>
        <div x-show="!collapsed" class="left-panel bg-slate-100 z-20">
            <?php echo e($sidebar); ?>

        </div>
    <?php endif; ?>

    <div class="relative" :class="collapsed ? 'md:col-span-3' : 'md:col-span-2'">
        <?php echo e($slot); ?>

        <?php if(isset($sidebar)): ?>
            <button @click="collapsed=!collapsed" class="absolute top-0 left-0 rounded flex items-center p-1 shadow-sm z-10 bg-slate-200/60">
                <?php echo $__env->make('components.icons.left-icon',['show' => '!collapsed'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php echo $__env->make('components.icons.right-icon',['show' => 'collapsed'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </button>
        <?php endif; ?>
    </div>
</main>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/layout/default.blade.php ENDPATH**/ ?>