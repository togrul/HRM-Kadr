<div class="flex flex-col space-y-2">
    <!--[if BLOCK]><![endif]--><?php if(!empty($selectedComponents[$i])): ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full sm:col-span-2 mt-3">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $selectedComponents[$i]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row => $_field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dynamic-input','data' => ['list' => $components,'field' => $service[$_field]['field'],'title' => $service[$_field]['title'],'type' => $_field,'model' => array_key_exists('model',$service[$_field]) ? ${$service[$_field]['model']} : null,'key' => $i,'selectedName' => array_key_exists('selectedName',$service[$_field]) ? $service[$_field]['selectedName'] : null,'searchField' => array_key_exists('searchField',$service[$_field]) ? $service[$_field]['searchField'] : null,'isCoded' => $coded_list[$i],'row' => $row,'disabled' => ($i+1) <= count($originalComponents)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('dynamic-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['list' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($components),'field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($service[$_field]['field']),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($service[$_field]['title']),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($_field),'model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(array_key_exists('model',$service[$_field]) ? ${$service[$_field]['model']} : null),'key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($i),'selectedName' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(array_key_exists('selectedName',$service[$_field]) ? $service[$_field]['selectedName'] : null),'searchField' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(array_key_exists('searchField',$service[$_field]) ? $service[$_field]['searchField'] : null),'isCoded' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($coded_list[$i]),'row' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row),'disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($i+1) <= count($originalComponents))]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8)): ?>
<?php $attributes = $__attributesOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8; ?>
<?php unset($__attributesOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8)): ?>
<?php $component = $__componentOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8; ?>
<?php unset($__componentOriginalc2b14bd8d5c641eb8fc7ec7c29fbe1a8); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/includes/order-templates/default.blade.php ENDPATH**/ ?>