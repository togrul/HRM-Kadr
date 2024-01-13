<header class="bg-white dark:bg-gray-800 shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 lg:px-0 grid gap-4 grid-cols-2 sm:grid-cols-6">
        <?php $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menuItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a 
            href="<?php echo e(route($menuItem->url)); ?>" 
            wire:navigate
            class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                "bg-$menuItem->color-100 rounded-lg shadow-sm px-4 py-3 flex items-center space-x-2 transition-all duration-300 hover:bg-$menuItem->color-200",
                "border-2 border-$menuItem->color-400" => request()->routeIs($menuItem->url)
            ]); ?>"
        >        
            <?php echo $menuItem->icon; ?>                                      
            <span class="font-medium"><?php echo e(__($menuItem->name)); ?></span>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
       
    </div>
</header><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/includes/header.blade.php ENDPATH**/ ?>