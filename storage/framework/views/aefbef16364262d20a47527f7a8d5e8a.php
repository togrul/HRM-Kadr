<div class="flex-col">
    <table>
        <thead>
            <tr>
                <th class="caption-table" colspan="5">10. Silahlı Qüvvələrdə və hüquq-mühafizə orqanlarında xidməti</th>
            </tr>
            <tr>
                <th style="width:80px;padding-top: 0;padding-bottom: 0;" >
                    <div style="">
                        <p style="margin: 0;">Nə <br/> vaxtdan</p>
                        <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                    </div>
                </th>
                <th style="width:80px;padding-top: 0;padding-bottom: 0;">
                    <div style="">
                        <p style="margin: 0;">Nə <br/> vaxtadək</p>
                        <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                    </div>
                </th>
                <th style="padding-top: 0;padding-bottom: 0;">Vəzifəsi</th>
                <th>
                    Orqanın, hissənin, dəstənin, <br> təhsil müəssisəsinin adı
                </th>
                <th style="padding-top: 0;padding-bottom: 0;">əmr kim <br> tərəfindən <br> verilib, <br> əmrin №-si və <br> tarixi</th>
            </tr>
        </thead>
        <tbody>
        <?php $__currentLoopData = $personnel->specialServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $special): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e(\Carbon\Carbon::parse($special->join_date)->format('d.m.Y')); ?></td>
                <td><?php echo e(\Carbon\Carbon::parse($special->leave_date)->format('d.m.Y')); ?></td>
                <td><?php echo e($special->position); ?></td>
                <td><?php echo e($special->company_name); ?></td>
                <td><?php echo e($special->order_given_by); ?>, <?php echo e($special->order_no); ?>, <?php echo e(\Carbon\Carbon::parse($special->order_date)->format('d.m.Y')); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php for($i = 0;$i < 2;$i++): ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/partials/page4-personnel.blade.php ENDPATH**/ ?>