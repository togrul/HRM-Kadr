<div class="flex flex-col space-y-2 px-4 py-2 justify-start">
    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $_order_categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <h1 class="font-medium text-neutral-600"><?php echo e($category->{"name_".config('app.locale')}); ?></h1>
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $category->orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:key="<?php echo e($order->id); ?>" wire:click="selectOrder('<?php echo e($order->id); ?>')"
                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'appearance-none bg-neutral-200/40 shadow-md rounded-xl border border-neutral-200 py-2 px-4 transition-all duration-300',
                    'text-neutral-600' => $order->id != $selectedOrder,
                    'text-emerald-500' => $order->id == $selectedOrder,
                ]); ?>"
            >
                <?php echo e($order->name); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/structure/orders.blade.php ENDPATH**/ ?>