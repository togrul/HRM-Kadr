<div>
    <div style="display: flex;justify-content: flex-end;align-items: center;">
        <h2 style="font-size: 15px;text-align: center;margin: 0;font-weight: bold;"><span style="text-decoration: underline;">Məxfi</span> <br/> (doldurulduqda)</h2>
    </div>

    <div class="flex-center">
        <h1 style="font-size: 16px;text-align: center;margin: 0;font-weight: bold;">Azərbaycan Respublikası <br/> Prezidentinin Təhlükəsizlik Xidməti</h1>
    </div>
</div>

<h1 style="text-align: center;padding-top:90px; padding-bottom:10px;letter-spacing: 5px; font-size: 26px;">XİDMƏT DƏFTƏRÇƏSİ</h1>

<div class="flex-center" style="padding: 10px 0;">
    <div style="display: flex;align-items: end;">
        <span style="font-weight: 600;padding: 1px 0;font-size: 12px;">Şəxsi nömrəsi</span>
        <span style="margin-left: 0.5rem;line-height:2;width: 150px;height: 30px;border-bottom: 1px solid #000;"><?php echo e($personnel->tabel_no); ?></span>
    </div>
</div>

<div class="flex-col" style="justify-content: center;align-items: center;">
    <span style="width: 100%;height: 30px;border-bottom: 1px solid #000;line-height: 2;font-style: italic;text-align: center"><?php echo e($personnel->fullname); ?></span>
    <span style="font-weight: 500;font-style: italic;font-size: 11px;">( soyadı,</span>
</div>

<div class="flex-col" style="justify-content: center;align-items: center;">
    <span style="width: 100%;height: 30px;border-bottom: 1px solid #000;"></span>
    <span style="font-weight: 500;font-style: italic; font-size: 11px;">adı və atasının adı )</span>
</div>

<div class="flex-col" style="width: 100%;margin-top: 30px;">
    <table>
        <thead>
            <th>Hərbi və ya xüsusi rütbə</th>
            <th>əmr kim tərəfindən verilib,əmrin №-si və tarixi</th>
            <th>Hərbi və ya xüsusi rütbə</th>
            <th>əmr kim tərəfindən verilib,əmrin №-si və tarixi</th>
        </thead>
        <tbody>
        <?php
            $rankChunks = $personnel->ranksASC->chunk(18);
            $maxRows = 18;
        ?>
        <?php for($i = 0; $i < $maxRows; $i++): ?>
            <tr>
                <?php if(isset($rankChunks[0][$i])): ?>
                    <td><?php echo e($rankChunks[0][$i]->rank->name); ?></td>

                    <td>AR PTX rəisinin əmri №<?php echo e($rankChunks[0][$i]->order_no); ?> <?php echo e($rankChunks[0][$i]->order_date->format('d.m.Y')); ?></td>
                <?php else: ?>
                    <td></td>
                    <td></td>
                <?php endif; ?>

                <?php if(isset($rankChunks[1][$i])): ?>
                    <td><?php echo e($rankChunks[1][$i]->rank->name); ?></td>
                    <td><?php echo e($rankChunks[1][$i]->order_given_by); ?>, <?php echo e($rankChunks[1][$i]->order_no); ?> <?php echo e($rankChunks[1][$i]->order_date->format('d.m.Y')); ?></td>
                <?php else: ?>
                    <td></td>
                    <td></td>
                <?php endif; ?>
            </tr>
        <?php endfor; ?>
















        </tbody>
    </table>
</div>

<div class="flex-between" style="width: 100%;">
    <div style="display: flex;align-items: end; width: 30%;">
        <span style="width: 20%;height: 30px;border-bottom: 1px solid #000;"></span>
        <span style="padding: 1px 0;font-size: 14px;">şəxsi №-li jetonu aldım</span>
    </div>

    <div style="display: flex;align-items: end;width: 30%;">
        "<span style="width:25% ;height: 30px;border-bottom: 1px solid #000;"></span>"
        <span style="width: 60%;height: 30px;border-bottom: 1px solid #000;"></span>
        <span style="font-size: 14px;">20</span><span style="width: 15%;height: 30px;border-bottom: 1px solid #000;"></span> <span style="font-size: 14px;">ildə</span>
    </div>

    <div class="flex-col" style="justify-content: center;align-items: center; width: 30%;">
        <span style="width: 100%;height: 50px;border-bottom: 1px solid #000;"></span>
        <span style="font-weight: 500;font-style: italic;font-size: 11px;">imzası</span>
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/prints/partials/page1-personnel.blade.php ENDPATH**/ ?>