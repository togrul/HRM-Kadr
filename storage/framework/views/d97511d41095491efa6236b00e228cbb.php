<div style="margin-top:15px;display:grid;grid-template-columns: repeat(4, minmax(0, 1fr));align-items: center;width: 100%;border:1px solid #000;">
    <h3 style="text-align: justify;padding: 0 5px;font-size: 16px;">17. Atasının və anasının soyadı, adı, atasının adı və yaşadığı ünvan</h3>
    <div style="display: flex;flex-direction: column;grid-column: span 3 / span 3; border-left: 1px solid #000;width: 100%;<?php if(count($personnel->fatherMother) > 0): ?> min-height: 175px; padding: 5px; <?php endif; ?>">
        <?php if(count($personnel->fatherMother) > 0): ?>
            <?php $__currentLoopData = $personnel->fatherMother; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <?php echo e($fm->kinship->{"name_".config('app.locale')}); ?> - <?php echo e($fm->fullname); ?>, <?php echo e($fm->residental_address); ?>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <?php for($i = 0;$i < 7;$i++): ?>
                <div style="<?php if($i < 6): ?> border-bottom: 1px solid #000; <?php endif; ?> height: 25px;"></div>
            <?php endfor; ?>
        <?php endif; ?>

    </div>
</div>

<div style="display: flex;align-items: center;">
    <h3 style="font-size: 16px;">18. Ailə vəziyyəti <span style="font-weight: 400;font-size: 14px;">(subay, evli)</span></h3>
    <span style="margin-left: 0.5rem;line-height:2;width: 35%;height: 30px;border-bottom: 1px solid #000;"><?php echo e($personnel->idDocuments?->is_married ? 'Evli' : 'Subay'); ?></span>
</div>

<table style="width: 100%;">
    <thead>
        <th style="padding-top: 0;padding-bottom: 0;">Həyat yoldaşı və uşaqlarının <br> soyadı, adı və atasının adı</th>
        <th style="padding-top: 0;padding-bottom: 0;">Qohumluq <br> dərəcəsi</th>
        <th style="padding-top: 0;padding-bottom: 0;">Nə vaxt və <br> harada anadan <br> olub</th>
        <th style="padding-top: 0;padding-bottom: 0;">Evlənmək və doğum haqqında <br> şəhadətnamənin nömrəsi</th>
    </thead>
    <tbody>
        <?php $__currentLoopData = $personnel->wifeChildren; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td style="font-size: 11px;padding: 5px;"><?php echo e($wf->fullname); ?></td>
                <td style="font-size: 11px;padding: 5px;"><?php echo e($wf->kinship->{"name_".config('app.locale')}); ?></td>
                <td style="font-size: 11px;padding: 5px;"><?php echo e(\Carbon\Carbon::parse($wf->birthdate)->format('d.m.Y')); ?>, <?php echo e($wf->birth_place); ?></td>
                <td>
                    <div class="flex-col" style="font-size: 11px;padding: 3px 0;">
                            <?php if(!empty($wf->birth_certificate_number)): ?>
                                <div style="display: flex;align-items: center;">
                                    <span style="margin-right: 3px;">Doğum şəhadətnaməsi #:</span>
                                    <span><?php echo e($wf->birth_certificate_number); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($wf->marriage_certificate_number)): ?>
                                <div style="display: flex;align-items: center;">
                                    <span style="margin-right: 3px;">Evlilik şəhadətnaməsi #:</span>
                                    <span><?php echo e($wf->marriage_certificate_number); ?></span>
                                </div>
                            <?php endif; ?>
                    </div>
                </td>
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

<div style="display: flex;flex-direction:column;justify-content: start;padding: 5px 0;">
    <h3 style="margin-bottom: 0;margin-top: 8px;font-size: 16px;">19. Yaşadığı ünvan</h3>

    <?php if(!empty($personnel->residental_address)): ?>
        <div style="border-bottom: 1px solid #000; min-height: 40px; display: flex;align-items: flex-end;padding:5px 0;">
            <?php echo e($personnel->residental_address); ?>

        </div>
    <?php else: ?>
        <?php for($i = 0;$i < 2;$i++): ?>
            <div style="border-bottom: 1px solid #000; height: 25px;"></div>
        <?php endfor; ?>
    <?php endif; ?>


</div>

<div style="display: flex;justify-content: end;align-items: center;margin-top: 10px;">
    <div style="display: flex;align-items: end;width: 80%;">
        <span style="margin-right: 10px;font-size: 16px;">Xidmət siyahısı tərtib olunub</span>
        "<span style="width:12% ;height: 30px;border-bottom: 1px solid #000;"></span>"
        <span style="width: 28%;height: 30px;border-bottom: 1px solid #000;"></span>
        20<span style="width: 8%;height: 30px;border-bottom: 1px solid #000;"></span>ildə
    </div>
</div>

<div style="display: flex;width: 100%;">
    <h3 style="padding: 0 10px;width: 70%;font-size: 16px;">Kadrlar idarəsinin (şöbəsinin)</h3>
    <div class="flex-col" style="justify-content: start;align-items: center;width: 100%;">
        <span style="width: 100%;height: 35px;border-bottom: 1px solid #000;"></span>
        <span style="font-weight: 500;font-style: italic; font-size: 11px;">(vəzifəsi)</span>
        <span style="width: 100%;height: 30px;border-bottom: 1px solid #000;"></span>
        <span style="font-weight: 500;font-style: italic; font-size: 11px;">(rütbəsi, imzası, soyadı)</span>
    </div>
</div>

<div style="display: flex;width: 100%;">
    <h3 style="padding: 0 10px;width: 70%;font-size: 16px;">Kadrlar idarəsinin (şöbəsinin)</h3>
    <div class="flex-col" style="justify-content: start;align-items: center;width: 100%;">
        <span style="width: 100%;height: 32px;border-bottom: 1px solid #000;"></span>
        <span style="font-weight: 500;font-style: italic; font-size: 11px;">(rütbəsi, imzası, soyadı)</span>
    </div>
</div>

<div class="flex-center">
    <p style="margin-top: 10px;margin-bottom:15px;font-size: 12px;">Xidmət siyahısında qeyd olunan məlumatları təsdiq etmək üçün hərbi qulluqçunun imzası</p>
</div>

<div style="display: grid;grid-template-columns: repeat(2, minmax(0, 1fr));align-items: center;gap: 1rem; ">
    <?php for($i = 0;$i < 8;$i++): ?>
        <div style="display: flex;align-items: end;width: 100%;height: 8px;">
            "<span style="width:13% ;height: 10px;border-bottom: 1px solid #000;"></span>"
            <span style="width: 51%;height: 10px;border-bottom: 1px solid #000;"></span>
            <span style="font-size: 14px">20</span><span style="width: 13%;height: 10px;border-bottom: 1px solid #000;"></span><span style="font-size: 14px">ildə</span>
            <span style="width: 23%;height: 10px;border-bottom: 1px solid #000;"></span>
        </div>
    <?php endfor; ?>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/partials/page16-personnel.blade.php ENDPATH**/ ?>