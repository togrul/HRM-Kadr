<table>
    <thead>
        <tr>
            <th>#</th>
            <th><?php echo e(__('Structure')); ?></th>
            <th><?php echo e(__('Rank')); ?></th>
            <th><?php echo e(__('Fullname')); ?></th>
            <th><?php echo e(__('Location')); ?></th>
            <th><?php echo e(__('Start date')); ?></th>
            <th><?php echo e(__('End date')); ?></th>
            <th><?php echo e(__('Order type')); ?></th>
            <th><?php echo e(__('Order #')); ?></th>
            <th><?php echo e(__('Given by')); ?></th>
            <th><?php echo e(__('Given date')); ?></th>
            <th><?php echo e(__('Extra info')); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $report; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <th><?php echo e($loop->iteration); ?></th>
            <th><?php echo e($r['attributes']['$structure']['value'] ?? ''); ?> </th>
            <th><?php echo e($r['attributes']['$rank']['value'] ?? ''); ?> </th>
            <th><?php echo e($r['attributes']['$fullname']['value'] ?? ''); ?> </th>
            <th><?php echo e($r['location'] ?? ''); ?></th>
            <th><?php echo e($r['start_date']); ?></th>
            <th><?php echo e($r['end_date']); ?></th>
            <th><?php echo e($r['order']['order_type']['name'] ?? ''); ?> </th>
            <th><?php echo e($r['order_no']); ?></th>
            <th><?php echo e($r['order_given_by']); ?></th>
            <th><?php echo e($r['order_date']); ?></th>
            <th>
                    <?php if(isset($r['attributes']['$transportation'])): ?>
                        <?php echo e(__('Transportation')); ?>: <?php echo e(__($r['attributes']['$transportation']['value'])); ?>

                        <?php if(
                            $r['attributes']['$transportation']['value'] == \App\Enums\TransportationEnum::CAR->name
                            && !empty($r['attributes']['$car']['value'])
                        ): ?>
                            -  <?php echo e(__($r['attributes']['$car']['value'])); ?>,
                        <?php endif; ?>,
                    <?php endif; ?>
                    <?php if(isset($r['attributes']['$weapon'])): ?>
                        <?php echo e(__('Weapon')); ?>: <?php echo e(__($r['attributes']['$weapon']['value'])); ?>,
                    <?php endif; ?>
                    <?php if(isset($r['attributes']['$service_dog'])): ?>
                        <?php echo e(__('Service dog')); ?>: <?php echo e(__($r['attributes']['$service_dog']['value']) ? 'var' : 'yoxdur'); ?>

                    <?php endif; ?>
            </th>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/exports/business-trips.blade.php ENDPATH**/ ?>