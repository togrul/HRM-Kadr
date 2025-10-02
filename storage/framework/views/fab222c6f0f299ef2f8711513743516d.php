<header class="bg-white/80 dark:bg-gray-800 shadow-sm">
    <div class="max-w-7xl mx-auto py-3 px-4 lg:px-0 grid gap-4 grid-cols-2 sm:grid-cols-6">
        <?php $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menuItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($menuItem->permission?->name)): ?>
                <a href="<?php echo e(route($menuItem->url)); ?>" wire:navigate class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    "bg-$menuItem->color-100 rounded-md shadow-sm px-4 py-1 flex items-center space-x-2 cursor-pointer transition-all duration-300 hover:bg-$menuItem->color-200",
                    "border border-$menuItem->color-400" => request()->routeIs($menuItem->url),
                ]); ?>">
                    <?php echo $menuItem->icon; ?>

                    <span class="font-medium text-base text-gray-700"><?php echo e(__($menuItem->name)); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</header>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/includes/header.blade.php ENDPATH**/ ?>