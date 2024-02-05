<table>
    <thead>
        <tr>
            <th>#</th>
            <th><?php echo e(__('Fullname')); ?></th>
            <th><?php echo e(__('Structure')); ?></th>
            <th><?php echo e(__('Height')); ?></th>
            <th><?php echo e(__('Military service')); ?></th>
            <th><?php echo e(__('Phone')); ?></th>
            <th><?php echo e(__('Knowledge test')); ?></th>
            <th><?php echo e(__('Physical fitness exam')); ?></th>
            <th><?php echo e(__('Research result')); ?></th>
            <th><?php echo e(__('Research date')); ?></th>
            <th><?php echo e(__('Discrediting information')); ?></th>
            <th><?php echo e(__('Examination date')); ?></th>
            <th><?php echo e(__('Appeal date')); ?></th>
            <th><?php echo e(__('Application date')); ?></th>
            <th><?php echo e(__('Requisition date')); ?></th>
            <th><?php echo e(__('Initial documents')); ?></th>
            <th><?php echo e(__('Documents completeness')); ?></th>
            <th><?php echo e(__('Attitude to military')); ?></th>
            <th><?php echo e(__('Characteristics')); ?></th>
            <th><?php echo e(__('HHK date')); ?></th>
            <th><?php echo e(__('HHK result')); ?></th>
            <th><?php echo e(__('Useless info')); ?></th>
            <th><?php echo e(__('Note')); ?></th>
            <th><?php echo e(__('Presented by')); ?></th>
            <th><?php echo e(__('Created date')); ?></th>
            <th><?php echo e(__('Status')); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $report; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <th><?php echo e($loop->iteration); ?></th>
            <th><?php echo e($r['name']); ?> <?php echo e($r['surname']); ?> <?php echo e($r['patronymic']); ?> </th>
            <th><?php echo e($r['structure']['name'] ?? ''); ?> </th>
            <th><?php echo e($r['height'] ?? ''); ?> </th>
            <th><?php echo e($r['military_service'] ?? ''); ?> </th>
            <th><?php echo e($r['phone'] ?? ''); ?> </th>
            <th><?php echo e($r['knowledge_test'] ?? ''); ?> </th>
            <th><?php echo e($r['physical_fitness_exam'] ?? ''); ?> </th>
            <th><?php echo e($r['research_result'] ?? ''); ?> </th>
            <th><?php echo e(!empty($r['research_date']) ? \Carbon\Carbon::parse($r['research_date'])->format('d.m.Y') : ''); ?> </th>
            <th><?php echo e($r['discrediting_information'] ?? ''); ?> </th>
            <th><?php echo e(!empty($r['examination_date']) ? \Carbon\Carbon::parse($r['examination_date'])->format('d.m.Y') : ''); ?> </th>
            <th><?php echo e(!empty($r['appeal_date']) ? \Carbon\Carbon::parse($r['appeal_date'])->format('d.m.Y') : ''); ?> </th>
            <th><?php echo e(!empty($r['application_date']) ? \Carbon\Carbon::parse($r['application_date'])->format('d.m.Y') : ''); ?> </th>
            <th><?php echo e(!empty($r['requisition_date']) ? \Carbon\Carbon::parse($r['requisition_date'])->format('d.m.Y') : ''); ?> </th>
            <th><?php echo e($r['initial_documents'] ?? ''); ?> </th>
            <th><?php echo e($r['documents_completeness'] ?? ''); ?> </th>
            <th><?php echo e($r['attitude_to_military'] ?? ''); ?> </th>
            <th><?php echo e($r['characteristics'] ?? ''); ?> </th>
            <th><?php echo e(!empty($r['hhk_date']) ? \Carbon\Carbon::parse($r['hhk_date'])->format('d.m.Y') : ''); ?> </th>
            <th><?php echo e($r['hhk_result'] ?? ''); ?> </th>
            <th><?php echo e($r['useless_info'] ?? ''); ?> </th>
            <th><?php echo e($r['note'] ?? ''); ?> </th>
            <th><?php echo e($r['presented_by'] ?? ''); ?> </th>
            <th><?php echo e(!empty($r['created_at']) ? \Carbon\Carbon::parse($r['created_at'])->format('d.m.Y H:i') : ''); ?> </th>
            <th><?php echo e($r['status']['name'] ?? ''); ?> </th>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/exports/candidate.blade.php ENDPATH**/ ?>