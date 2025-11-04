<table>
    <thead>
    <tr>
        <th>#</th>
        <th><?php echo e(__('Structure')); ?></th>
        <th><?php echo e(__('Position')); ?></th>
        <th><?php echo e(__('Fullname')); ?></th>
        <th><?php echo e(__('Location')); ?></th>
        <th><?php echo e(__('Start date')); ?></th>
        <th><?php echo e(__('End date')); ?></th>
        <th><?php echo e(__('Return work date')); ?></th>
        <th><?php echo e(__('Duration')); ?></th>
        <th><?php echo e(__('Order #')); ?></th>
        <th><?php echo e(__('Given by')); ?></th>
        <th><?php echo e(__('Given date')); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $report; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <th><?php echo e($loop->iteration); ?></th>
            <th><?php echo e($r['personnel']['structure']['name'] ?? ''); ?> </th>
            <th><?php echo e($r['personnel']['position']['name'] ?? ''); ?> </th>
            <th><?php echo e($r['personnel']['surname'] ?? ''); ?> <?php echo e($r['personnel']['name'] ?? ''); ?> <?php echo e($r['personnel']['patronymic'] ?? ''); ?> </th>
            <th><?php echo e($r['vacation_places']); ?></th>
            <th><?php echo e($r['start_date']); ?></th>
            <th><?php echo e($r['end_date']); ?></th>
            <th><?php echo e($r['return_work_date']); ?></th>
            <th><?php echo e($r['duration']); ?></th>
            <th><?php echo e($r['order_no']); ?></th>
            <th><?php echo e($r['order_given_by']); ?></th>
            <th><?php echo e($r['order_date']); ?></th>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/exports/vacations.blade.php ENDPATH**/ ?>