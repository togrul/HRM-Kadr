<div class="flex-col">
    <table>
        <thead>
           <tr>
               <th class="caption-table" colspan="4">11. Pensiya təyin edilərkən xidmət illərinin güzəştli hesablanmasına hüquq verən xidmət dövrləri</th>
           </tr>
            <tr>
                <th>Sənədin adı, №-si və tarixi</th>
                <th  style="width:40px;">əmsal</th>
                <th  style="width:90px;">
                    <div style="padding: 5px;">
                        <p style="margin: 0;">Nə vaxtdan</p>
                        <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                    </div>
                </th>
                <th style="width:90px;">
                    <div style="padding: 5px;">
                        <p style="margin: 0;">Nə vaxtadək</p>
                        <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                    </div>
                </th></tr>
        </thead>
        <tbody>
        <?php for($i = 0;$i < 26;$i++): ?>
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

<div style="display: flex;justify-content: start">
    <h3 style="font-size: 16px;">12. Xidməti vəzifələrini yerinə yetirərkən yaralanması, kontuziyaları (nə vaxt və harada); onların xüsusiyyətləri </h3>
</div>


<table style="width: 100%;">
    <thead style="display: none;">
        <th></th>
    </thead>
    <tbody>
    <?php if(count($personnel->injuries) > 0): ?>
        <?php $__currentLoopData = $personnel->injuries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $injury): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($injury->injury_type); ?> , <?php echo e(\Carbon\Carbon::parse($injury->date_time)->format('d.m.Y')); ?> , <?php echo e($injury->location); ?> , <?php echo e($injury->description); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php for($i = 0;$i < 1;$i++): ?>
            <tr>
                <td></td>
            </tr>
        <?php endfor; ?>
    <?php else: ?>
        <?php for($i = 0;$i < 3;$i++): ?>
            <tr>
                <td></td>
            </tr>
        <?php endfor; ?>
    <?php endif; ?>
    </tbody>
</table>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/partials/page5-personnel.blade.php ENDPATH**/ ?>