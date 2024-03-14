<div class="flex flex-col space-y-2 px-4 py-2 justify-start">
    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $_order_categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <h1 class="font-medium"><?php echo e($category->{"name_".config('app.locale')}); ?></h1>
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $category->orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:key="<?php echo e($order->id); ?>" wire:click="selectOrder('<?php echo e($order->id); ?>')"
                class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'appearance-none bg-slate-50 rounded-xl py-3 px-4 transition-all duration-300',
                    'text-slate-600' => $order->id != $selectedOrder,
                    'text-emerald-500' => $order->id == $selectedOrder,
                ]); ?>"
            >
                <?php echo e($order->name); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <!--[if ENDBLOCK]><![endif]-->
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/structure/orders.blade.php ENDPATH**/ ?>