<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'list', // arrayin adi
    'field', // hansi columndursa
    'title', // inputun basligi
    'type', // deyisenin adi
    'model' => null, //  list olanda foreach ucun model
    'key', // hansi key e aid datadir
    'selectedName' => null,
    'searchField' => null,
    'isCoded' => false, // kodu yoxsa tam adi gelsin
    'row',
    'disabled' => false
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'list', // arrayin adi
    'field', // hansi columndursa
    'title', // inputun basligi
    'type', // deyisenin adi
    'model' => null, //  list olanda foreach ucun model
    'key', // hansi key e aid datadir
    'selectedName' => null,
    'searchField' => null,
    'isCoded' => false, // kodu yoxsa tam adi gelsin
    'row',
    'disabled' => false
]); ?>
<?php foreach (array_filter(([
    'list', // arrayin adi
    'field', // hansi columndursa
    'title', // inputun basligi
    'type', // deyisenin adi
    'model' => null, //  list olanda foreach ucun model
    'key', // hansi key e aid datadir
    'selectedName' => null,
    'searchField' => null,
    'isCoded' => false, // kodu yoxsa tam adi gelsin
    'row',
    'disabled' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $input = match ($type)
    {
        '$structure_main','$position','$fullname','$rank','$transportation' => 'select',
        '$month','$name','$surname','$days','$location','$trip_start_month','$meeting_hour','$return_month','$car', '$weapon' => 'text-input',
        '$day','$year','$trip_start_day','$trip_start_year','$return_day' => 'numeric-input',
        '$structure' => 'radio-list',
        '$start_date','$end_date' => 'date-input'
    };

    $list_string = 'components';
?>

<!--[if BLOCK]><![endif]--><?php if($input == 'text-input'): ?>
    <div class="">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => ''.e($list_string).'.'.e($key).'.'.e($field).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => ''.e($list_string).'.'.e($key).'.'.e($field).'']); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['disabled' => ''.e($disabled).'','mode' => 'gray','name' => ''.e($list_string).'.'.e($key).'.'.e($field).'','wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['disabled' => ''.e($disabled).'','mode' => 'gray','name' => ''.e($list_string).'.'.e($key).'.'.e($field).'','wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ["{$list_string}.{$key}.{$field}"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
<?php elseif($input == 'numeric-input'): ?>
    <div>
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => ''.e($list_string).'.'.e($key).'.'.e($field).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => ''.e($list_string).'.'.e($key).'.'.e($field).'']); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['disabled' => $disabled,'mode' => 'gray','type' => 'number','name' => ''.e($list_string).'.'.e($key).'.'.e($field).'','wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($disabled),'mode' => 'gray','type' => 'number','name' => ''.e($list_string).'.'.e($key).'.'.e($field).'','wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ["{$list_string}.{$key}.{$field}"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
<?php elseif($input == 'select'): ?>
    <div class="flex flex-col">
        <?php
            ${$selectedName."Name"} = array_key_exists($key,$list) ? $list[$key][$field]['name'] : '---';
            ${$selectedName."Id"} = array_key_exists($key,$list) ? $list[$key][$field]['id'] : -1;
        ?>
        <?php if (isset($component)) { $__componentOriginald384098dd1216f6f264fe579adbe3c2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald384098dd1216f6f264fe579adbe3c2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list','data' => ['class' => 'w-full','disabled' => $disabled,'title' => $title,'mode' => 'gray','selected' => ${$selectedName.'Name'},'name' => ''.e($field).'Id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full','disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($disabled),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'mode' => 'gray','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(${$selectedName.'Name'}),'name' => ''.e($field).'Id']); ?>
            <!--[if BLOCK]><![endif]--><?php if(!empty($searchField)): ?>
                <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['@click.stop' => 'open = true','mode' => 'gray','name' => ''.e($searchField).'','wire:model.live' => ''.e($searchField).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click.stop' => 'open = true','mode' => 'gray','name' => ''.e($searchField).'','wire:model.live' => ''.e($searchField).'']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => 'setData(\''.e($list_string).'\',\''.e($field).'\',null,\'---\',null,'.e($key).')','selected' => '---' ==  ${$selectedName.'Name'},'wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setData(\''.e($list_string).'\',\''.e($field).'\',null,\'---\',null,'.e($key).')','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('---' ==  ${$selectedName.'Name'}),'wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'.id']); ?>
                ---
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $attributes = $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $component = $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model_item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    if(is_array($model_item))
                    {
                        $_id = $model_item['id'];
                        $_optionValue = $model_item['name'];
                        $_selected = $_id === ${$selectedName.'Id'};
                    }
                    else
                    {
                        $_id = $model_item->id;
                        $_optionValue = ($model_item->fullname_max ?? $model_item->name);
                        $_selected = $_id === ${$selectedName.'Id'};
                    }
                ?>
                <?php if (isset($component)) { $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.select-list-item','data' => ['wire:click' => '
                                        setData(\''.e($list_string).'\',\''.e($field).'\',null,\''.e($_optionValue).'\','.e($_id).','.e($key).');
                                        $dispatch(\'dynamicSelectChanged\',{value: '.e($_id).',field: \''.e($field).'\',rowKey: '.e($key).' })
                                    ','selected' => $_selected,'wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'.id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('select-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => '
                                        setData(\''.e($list_string).'\',\''.e($field).'\',null,\''.e($_optionValue).'\','.e($_id).','.e($key).');
                                        $dispatch(\'dynamicSelectChanged\',{value: '.e($_id).',field: \''.e($field).'\',rowKey: '.e($key).' })
                                    ','selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($_selected),'wire:model' => ''.e($list_string).'.'.e($key).'.'.e($field).'.id']); ?>
                    <?php echo e($_optionValue); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $attributes = $__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__attributesOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee)): ?>
<?php $component = $__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee; ?>
<?php unset($__componentOriginalfad9b9ef9db98dab13eefb5c81eb8bee); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald384098dd1216f6f264fe579adbe3c2f)): ?>
<?php $attributes = $__attributesOriginald384098dd1216f6f264fe579adbe3c2f; ?>
<?php unset($__attributesOriginald384098dd1216f6f264fe579adbe3c2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald384098dd1216f6f264fe579adbe3c2f)): ?>
<?php $component = $__componentOriginald384098dd1216f6f264fe579adbe3c2f; ?>
<?php unset($__componentOriginald384098dd1216f6f264fe579adbe3c2f); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ["{$list_string}.{$key}.{$field}.id"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>
<?php elseif($input == 'radio-list'): ?>
    <div class="flex flex-col space-y-1"
         x-data="{showStructures: false}"
    >
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => 'order.order_no']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'order.order_no']); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <div class="relative w-full">
            <button @click="showStructures = !showStructures"
                    class="appearance-none flex justify-center items-center w-full rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium"
            >
                <?php echo e(array_key_exists($field,$this->{$list_string}[$key])
                            ? $this->{$list_string}[$key][$field]['name']
                            : __('Structure')); ?>

            </button>
            <!--[if BLOCK]><![endif]--><?php if(!$disabled): ?>
            <div x-show="showStructures"
                 x-transition:enter="transition ease-in-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-y-0 -translate-y-1/2"
                 x-transition:enter-end="opacity-100 transform scale-y-100 translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300"
                 x-transition:leave-start="opacity-100 transform scale-y-100 translate-y-0"
                 x-transition:leave-end="opacity-0 transform scale-y-0 -translate-y-1/2"
                 class="z-[99999] flex px-4 py-3 bg-neutral-50 border border-gray-200 shadow-xl rounded absolute top-9 <?php echo e($row % 3 == 0 ? 'left-0' : 'right-0'); ?> w-full sm:max-w-xl md:max-w-screen-sm lg:max-w-screen-md min-w-full sm:w-screen "
            >
                <?php if (isset($component)) { $__componentOriginaldba916afd11383a7f76a85a00af04a8a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldba916afd11383a7f76a85a00af04a8a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.radio-tree.list','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('radio-tree.list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <?php
                        $wordSuffixService = new \App\Services\WordSuffixService();
                    ?>
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model->where('parent_id',$this->{$list_string}[$key]['structure_main_id']['id']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model_item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $_level_name = strtolower((collect(\App\Enums\StructureEnum::cases())->pluck('name','value')[$model_item->level]));

                            $_select_value = ($field == 'structure_id' && $isCoded)
                                            ? $model_item->code."{$wordSuffixService->getNumberSuffix($model_item->code)} {$_level_name}"
                                            : $model_item->name;
                        ?>
                        <?php if (isset($component)) { $__componentOriginal91d6979f2b66c7dd48cd128b9dd939f7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91d6979f2b66c7dd48cd128b9dd939f7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.radio-tree.item','data' => ['isCoded' => $isCoded,'listData' => $list_string,'field' => $field,'model' => $model_item,'key' => $key]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('radio-tree.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isCoded' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isCoded),'listData' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($list_string),'field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field),'model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($model_item),'key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key)]); ?>
                            <?php echo e($_select_value); ?>

                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91d6979f2b66c7dd48cd128b9dd939f7)): ?>
<?php $attributes = $__attributesOriginal91d6979f2b66c7dd48cd128b9dd939f7; ?>
<?php unset($__attributesOriginal91d6979f2b66c7dd48cd128b9dd939f7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91d6979f2b66c7dd48cd128b9dd939f7)): ?>
<?php $component = $__componentOriginal91d6979f2b66c7dd48cd128b9dd939f7; ?>
<?php unset($__componentOriginal91d6979f2b66c7dd48cd128b9dd939f7); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldba916afd11383a7f76a85a00af04a8a)): ?>
<?php $attributes = $__attributesOriginaldba916afd11383a7f76a85a00af04a8a; ?>
<?php unset($__attributesOriginaldba916afd11383a7f76a85a00af04a8a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldba916afd11383a7f76a85a00af04a8a)): ?>
<?php $component = $__componentOriginaldba916afd11383a7f76a85a00af04a8a; ?>
<?php unset($__componentOriginaldba916afd11383a7f76a85a00af04a8a); ?>
<?php endif; ?>
            </div>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

    </div>
    <?php elseif($input == 'date-input'): ?>
    <div class="flex flex-col">
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => ''.e($list_string).'.'.e($key).'.'.e($field).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => ''.e($list_string).'.'.e($key).'.'.e($field).'']); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal36038ba5ddba347b69d2b76bc4612d11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pikaday-input','data' => ['mode' => 'gray','name' => ''.e($list_string).'.'.e($key).'.'.e($field).'','format' => 'Y-MM-DD','wire:model.live' => ''.e($list_string).'.'.e($key).'.'.e($field).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('pikaday-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mode' => 'gray','name' => ''.e($list_string).'.'.e($key).'.'.e($field).'','format' => 'Y-MM-DD','wire:model.live' => ''.e($list_string).'.'.e($key).'.'.e($field).'']); ?>
             <?php $__env->slot('script', null, []); ?> 
                $el.onchange = function () {
                window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('<?php echo e($list_string); ?>.<?php echo e($key); ?>.<?php echo e($field); ?>', $el.value);
                }
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $attributes = $__attributesOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__attributesOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11)): ?>
<?php $component = $__componentOriginal36038ba5ddba347b69d2b76bc4612d11; ?>
<?php unset($__componentOriginal36038ba5ddba347b69d2b76bc4612d11); ?>
<?php endif; ?>
        <!--[if BLOCK]><![endif]--><?php $__errorArgs = ["<?php echo e($list_string); ?>.<?php echo e($key); ?>.<?php echo e($field); ?>"];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <?php if (isset($component)) { $__componentOriginala61a9a091bbbf95d1addcb0ba0326332 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.validation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('validation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo e($message); ?>  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $attributes = $__attributesOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__attributesOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332)): ?>
<?php $component = $__componentOriginala61a9a091bbbf95d1addcb0ba0326332; ?>
<?php unset($__componentOriginala61a9a091bbbf95d1addcb0ba0326332); ?>
<?php endif; ?>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

<?php endif; ?><!--[if ENDBLOCK]><![endif]-->
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/dynamic-input.blade.php ENDPATH**/ ?>