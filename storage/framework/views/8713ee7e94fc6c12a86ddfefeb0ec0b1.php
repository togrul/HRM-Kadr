<table style="width: 100%;">
    <thead>
        <tr>
            <th class="caption-table" colspan="4">14. Xarici ezamiyyətlər</th>
        </tr>
       <tr>
           <th>Harada, nə məqsədlə olub</th>
           <th style="width: 80px;">Kimin əmri ilə <br> <span style="font-weight: 400;">(əmrin №-si və tarixi)</span></th>
           <th style="width: 90px;">
               <div style="padding: 5px;">
                   <p style="margin: 0;">Nə vaxtdan</p>
                   <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
               </div>
           </th>
           <th style="width: 90px;">
               <div style="padding: 5px;">
                   <p style="margin: 0;">Nə vaxtadək</p>
                   <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
               </div>
           </th>
       </tr>
    </thead>
    <tbody>
        <?php
            $tripModel = $personnel->businessTrips()->foreignBusinessTrip()->get();
        ?>
        <?php $__currentLoopData = $tripModel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $foreign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($foreign->location ? $foreign->location . ',' : ''); ?> <?php echo e($foreign->description); ?></td>
                <td><?php echo e($foreign->order->given_by); ?> <?php echo e($foreign->order_no); ?> <?php echo e($foreign->order_date->format('d.m.Y')); ?></td>
                <td><?php echo e($foreign->start_date->format('d.m.Y')); ?></td>
                <td><?php echo e($foreign->end_date->format('d.m.Y')); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php for($i = 0;$i < 18 - $tripModel->count();$i++): ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

<div style="display: flex;flex-direction:column;justify-content: start">
    <h3 style="margin-bottom: 5px;font-size: 16px;">15. Hansı seçki orqanlarına seçilmişdir <span style="font-weight: 400;font-size: 16px;">(harada və nə vaxt)</span></h3>
    <?php if(count($personnel->elections) > 0): ?>
        <?php $__currentLoopData = $personnel->elections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $election): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="border-bottom: 1px solid #000; height: <?php echo e($i == 0 ? 10 : 25); ?>px;">
                <?php echo e($election->election_type); ?> - <?php echo e($election->location); ?> - <?php echo e(\Carbon\Carbon::parse($election->elected_date)->format('d.m.Y')); ?>

            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php for($i = 0;$i < 1;$i++): ?>
            <div style="border-bottom: 1px solid #000; height: 25px;"></div>
        <?php endfor; ?>
    <?php else: ?>
        <?php for($i = 0;$i < 3;$i++): ?>
            <div style="border-bottom: 1px solid #000; height: <?php echo e($i == 0 ? 10 : 25); ?>px;"></div>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<div style="display: flex;flex-direction:column;justify-content: start">
    <h3 style="margin-bottom: 5px;font-size: 16px;">16. Əsirlikdə olubmu <span style="font-weight: 400;font-size: 16px;">(hansı şəraitdə, harada, nə vaxt əsir düşüb və azad olunub)</span></h3>
    <?php if(count($personnel->captives) > 0): ?>
        <?php $__currentLoopData = $personnel->captives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $captive): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="border-bottom: 1px solid #000; min-height: <?php echo e($i == 0 ? 10 : 25); ?>px;">
                <?php echo e(\Carbon\Carbon::parse($captive->taken_captive_date)->format('d.m.Y')); ?> tarixində <?php echo e($captive->location); ?> ərazisində
                <?php echo e($captive->condition); ?> əsirlikdə olub.
                <?php if(!empty($captive->release_date)): ?>
                    <?php echo e(\Carbon\Carbon::parse($captive->release_date)->format('d.m.Y')); ?> tarixində əsirlikdən azad olub.
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php for($i = 0;$i < 1;$i++): ?>
            <div style="border-bottom: 1px solid #000; height: 25px;"></div>
        <?php endfor; ?>
    <?php else: ?>
        <?php for($i = 0;$i < 6;$i++): ?>
            <div style="border-bottom: 1px solid #000; height: <?php echo e($i == 0 ? 10 : 25); ?>px;">
            </div>
        <?php endfor; ?>
    <?php endif; ?>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/partials/page15-personnel.blade.php ENDPATH**/ ?>