<table>
    <thead>
        <tr>
            <th>#</th>
            <th><?php echo e(__('Person')); ?></th>
            <th><?php echo e(__('Teacher')); ?></th>
            
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $report['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <th><?php echo e($loop->iteration); ?></th>
                <th><?php echo e($r['name'] ?? ''); ?> <?php echo e($r['surname'] ?? ''); ?> <?php echo e($r['patronymic'] ?? ''); ?></th>
                
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/exports/personnel.blade.php ENDPATH**/ ?>