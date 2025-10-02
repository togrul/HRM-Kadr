<table class="table-v-2" style="width: 100%;margin-top: 10px;">
    <tr style="height: 60px;">
        <th rowspan="1">1. Anadan olduğu gün, ay və il</th>
        <td><?php echo e(\Carbon\Carbon::parse($personnel->birthdate)->format('d.m.Y')); ?></td>
    </tr>

    <tr style="height: 90px;">
        <th>2. Anadan olduğu yer <br/>
            <span style="font-weight: 400;font-size:11px;">(doldurulduğu günə qədər inzibati bölgü üzrə)</span>
        </th>
        <td>
            <?php echo e($personnel->idDocuments?->bornCountry?->title); ?>,<?php echo e($personnel->idDocuments?->bornCity?->name); ?>

        </td>
    </tr>
    <tr>
        <th>3. Milliyəti</th>
        <td><?php echo e($personnel->nationality?->title); ?></td>
    </tr>
    <tr>
        <th>4. Sosial mənşəyi</th>
        <td><?php echo e($personnel->socialOrigin?->name); ?></td>
    </tr>
    <tr>
        <th style="padding: 0;">
            <div style="display: grid;grid-template-columns: repeat(5, minmax(0, 1fr));align-items: center">
                <h2 style="margin-left: 10px;grid-column: span 2 / span 2;">5. Təhsili</h2>
                <div class="flex-col" style="height:580px;grid-column: span 3 / span 3; border-left: 1px solid #000;">
                    <div class="flex-col seperated-column" style="border-bottom: 1px solid #000;">
                        <h2>a) Mülki</h2>
                        <span style="font-weight: 400;font-size:11px;">(nə vaxt və hansı təhsil müəssisələrini bitirib; ixtisası)</span>
                    </div>
                    <div class="flex-col seperated-column">
                        <h2>b) Hərbi <br/> (xüsusi)</h2>
                        <span style="font-weight: 400;font-size:11px;">(nə vaxt və hansı təhsil müəssisələrini və kursları bitirib; ixtisası)</span>
                    </div>
                </div>
            </div>
        </th>
        <td style="padding: 0">
            <div class="flex-col" style="height: 100%;">
                <div style="height: 50%;border-bottom: 1px solid #000;">
                    <?php if(!empty($personnel['education'])): ?>
                        <?php if(!$personnel['education']['is_military']): ?>
                            <?php if (isset($component)) { $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.education-list','data' => ['name' => $personnel->education->institution->name,'specialty' => $personnel->education->specialty,'admissionYear' => $personnel->education->admission_year,'graduatedYear' => $personnel->education->graduated_year]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('education-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->institution->name),'specialty' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->specialty),'admission_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->admission_year),'graduated_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->graduated_year)]); ?>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $attributes = $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $component = $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>

                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if(count($personnel['extraEducations']) > 0): ?>
                        <?php $__currentLoopData = $personnel['extraEducations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $extraEdu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(!$extraEdu['is_military']): ?>
                                    <?php if (isset($component)) { $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.education-list','data' => ['name' => $extraEdu->institution->name,'specialty' => $extraEdu->education_program_name,'admissionYear' => $extraEdu->admission_year,'graduatedYear' => $extraEdu->graduated_year]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('education-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->institution->name),'specialty' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->education_program_name),'admission_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->admission_year),'graduated_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->graduated_year)]); ?>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $attributes = $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $component = $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>
                                <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>

                <div style="height: 50%;">
                    <?php if(!empty($personnel->education)): ?>
                        <?php if($personnel->education->is_military): ?>
                            <?php if (isset($component)) { $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.education-list','data' => ['name' => $personnel->education->institution->name,'specialty' => $personnel->education->specialty,'admissionYear' => $personnel->education->admission_year,'graduatedYear' => $personnel->education->graduated_year]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('education-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->institution->name),'specialty' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->specialty),'admission_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->admission_year),'graduated_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($personnel->education->graduated_year)]); ?>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $attributes = $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $component = $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>

                        <?php endif; ?>
                    <?php endif; ?>
                        <?php if(count($personnel->extraEducations) > 0): ?>
                            <?php $__currentLoopData = $personnel->extraEducations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $extraEdu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($extraEdu->is_military): ?>
                                    <?php if (isset($component)) { $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.education-list','data' => ['name' => $extraEdu->institution->name,'specialty' => $extraEdu->education_program_name,'admissionYear' => $extraEdu->admission_year,'graduatedYear' => $extraEdu->graduated_year]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('education-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->institution->name),'specialty' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->education_program_name),'admission_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->admission_year),'graduated_year' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($extraEdu->graduated_year)]); ?>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $attributes = $__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__attributesOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4)): ?>
<?php $component = $__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4; ?>
<?php unset($__componentOriginala3a75ce342e159cafcd8a59bc5a2d0e4); ?>
<?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                </div>
            </div>
        </td>
    </tr>

    <tr style="height: 90px;">
        <th>6. Hansı xarici dilləri bilir</th>
        <td>
                <?php $__currentLoopData = $personnel->foreignLanguages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo e($lang->language->name); ?> - <?php echo e($lang->knowledge_status); ?> <?php if(!$loop->last): ?> , <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </td>
    </tr>

    <tr style="height: 90px;">
        <th>7. Elmi dərəcələri, elmi adı və verildiyi tarix </th>
        <td>
            <?php if(count($personnel->degreeAndNames) > 0): ?>
                <?php $__currentLoopData = $personnel->degreeAndNames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $degree): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="padding: 3px;">
                        <span><?php echo e($degree->degreeAndName->name); ?></span>,
                        <span><?php echo e($degree->science); ?></span> -
                        <span><?php echo e(\Carbon\Carbon::parse($degree->given_date)->format('d.m.Y')); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr style="height: 60px;">
        <th>8. Hansı elmi əsərləri və ixtiraları var</th>
        <td><?php echo e($personnel['scientific_works_inventions']); ?></td>
    </tr>
</table>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/prints/partials/page2-personnel.blade.php ENDPATH**/ ?>