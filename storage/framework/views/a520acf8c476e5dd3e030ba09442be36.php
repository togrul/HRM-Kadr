<div class="flex-col">
    <table>
        <thead>
            <tr>
                <th class="caption-table" colspan="4">9. Əmək fəaliyyəti</th>
            </tr>
            <tr>
                <th style="padding: 0;" colspan="2">
                    <div class="flex-col">
                        <div class="flex-center" style="border-bottom: 1px solid #000;padding: 3px 0;">
                            Tarix
                        </div>
                        <div style="display: flex;width: 100%;">
                            <div style="border-right: 1px solid #000; padding: 3px 5px;width: 50%;">
                                <p style="margin: 0;">Nə vaxtdan</p>
                                <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                            </div>
                            <div style="padding: 5px;width: 50%;">
                                <p style="margin: 0;">Nə vaxtadək</p>
                                <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                            </div>
                        </div>
                    </div>
                </th>

                <th>
                    <p style="margin: 0;">İş yeri <span style="font-weight: 400;">(müəssisənin,təşkilatın və s. adı)</span> <br> və harada yerləşir <span style="font-weight: 400;">(şəhər, rayon ,kənd)</span></p>
                </th>
                <th>Vəzifəsi</th>
            </tr>

        </thead>
        <tbody>
         <?php $__currentLoopData = $personnel->laborActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $labor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
             <tr>
                 <td><?php echo e(\Carbon\Carbon::parse($labor->join_date)->format('d.m.Y')); ?></td>
                 <td><?php echo e(\Carbon\Carbon::parse($labor->leave_date)->format('d.m.Y')); ?></td>
                 <td><?php echo e($labor->company_name); ?></td>
                 <td><?php echo e($labor->position); ?></td>
             </tr>
         <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
         <?php for($i = 0;$i < 2;$i++): ?>
             <tr>
                 <td></td>
                 <td></td>
                 <td></td>
                 <td></td>
             </tr>
         <?php endfor; ?>
        </tbody>
    </table>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/partials/page3-personnel.blade.php ENDPATH**/ ?>