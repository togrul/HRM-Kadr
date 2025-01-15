<table style="width: 100%;">
    <thead>
        <tr>
            <th class="caption-table" colspan="3">13. Azərbaycan Respublikasının, yaxud xarici dövlətlərin hansı orden və medalları ilə təltif olunub (həmçinin Azərbaycan Milli Qəhrəmanı və s. adlar qeyd olunmalıdır).</th>
        </tr>
        <tr>
            <th style="padding-top: 0;padding-bottom: 0;">Ordenin, medalların adı və hansı fəxri ada <br> layiq gorülüb</th>
            <th style="padding-top: 0;padding-bottom: 0;">Nə üçün təltif olunmuşdur (döyüşdə <br> fərqlənməyə, uzun müddətli xidmətə görə)</th>
            <th style="padding-top: 0;padding-bottom: 0;">Təltif və fəxri adın <br> verilməsi haqqında <br> fərmanın, əmrin kim <br> tərəfindən verilmişdir,<br> əmrin №-si və tarixi.</th>
        </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $personnel->awards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $award): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($award->award->name); ?></td>
            <td><?php echo e($award->reason); ?></td>
            <td><?php echo e($award->order_given_by ? $award->order_given_by . ',' : ''); ?> <?php echo e($award->order_no); ?> <?php echo e(\Carbon\Carbon::parse($award->order_date)->format('d.m.Y')); ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php for($i = 0;$i < (18 - $personnel->awards->count());$i++): ?>
        <tr>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    <?php endfor; ?>
    </tbody>
</table>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/partials/page14-personnel.blade.php ENDPATH**/ ?>