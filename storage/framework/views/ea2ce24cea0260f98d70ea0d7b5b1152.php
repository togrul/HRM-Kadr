<table>
    <thead>
        <tr>
            <th>#</th>
            <th><?php echo e(__('Structure')); ?></th>
            <th><?php echo e(__('Position')); ?></th>
            <th><?php echo e(__('Vacant')); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $report; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <th><?php echo e($loop->iteration); ?></th>
                <th><?php echo e($r['structure']['name'] ?? ''); ?> </th>
                <th><?php echo e($r['position']['name'] ?? ''); ?> </th>
                <th><?php echo e($r['vacant'] ?? ''); ?> </th>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/exports/vacancies.blade.php ENDPATH**/ ?>