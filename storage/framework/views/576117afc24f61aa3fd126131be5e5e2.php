<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'model',
    'listData' => 'componentForms',
    'field' => "",
    'key' => 0,
    'isCoded' => false,
    'selectedId' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'model',
    'listData' => 'componentForms',
    'field' => "",
    'key' => 0,
    'isCoded' => false,
    'selectedId' => null,
]); ?>
<?php foreach (array_filter(([
    'model',
    'listData' => 'componentForms',
    'field' => "",
    'key' => 0,
    'isCoded' => false,
    'selectedId' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $wordSuffixService = new \App\Services\WordSuffixService();
    $currentSelection = $selectedId;
    if ($currentSelection === null) {
        $rawValue = data_get($this->{$listData}[$key] ?? [], $field);
        if (method_exists($this, 'componentFieldValue')) {
            $currentSelection = $this->componentFieldValue($key, $field);
        } else {
            $currentSelection = is_array($rawValue) ? ($rawValue['id'] ?? null) : $rawValue;
        }
    }
?>

<li x-data="{openSubStructure: false}" class="py-1">
    <div class="flex justify-between w-full items-center">
        <div class="flex justify-start items-center space-x-2">
            <!--[if BLOCK]><![endif]--><?php if(count($model->subs) > 0): ?>
            
            <button @click="openSubStructure = !openSubStructure" class="appearance-none flex justify-center items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-500">
                    <path x-show="openSubStructure"
                          stroke-linecap="round"
                          stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                          x-transition:enter="transition ease-out duration-300"
                          x-transition:enter-start="opacity-0 scale-90"
                          x-transition:enter-end="opacity-100 scale-100"
                          x-transition:leave="transition ease-in duration-200"
                          x-transition:leave-start="opacity-100 scale-100"
                          x-transition:leave-end="opacity-0 scale-90"
                    />
                    <path
                        x-show="!openSubStructure"
                        stroke-linecap="round"
                        stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                    />
                </svg>
            </button>
            <?php else: ?>
                <span class="w-4 h-4"></span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            
            <span class="text-sm font-medium"><?php echo e($slot); ?></span>
        </div>
        
        <button
            wire:click.prevent="setStructure(<?php echo e($model->id); ?>,'<?php echo e($listData); ?>','<?php echo e($field); ?>',<?php echo e($key); ?>,<?php echo e($isCoded ? 1 : 0); ?>)"
            class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'appearance-none rounded-full w-6 h-6 border p-[3px] flex justify-center items-center transition-all duration-300',
                'border-gray-300 bg-white' => $model->id != $currentSelection,
                'border-green-500 bg-green-200' => $model->id == $currentSelection
            ]); ?>"
        >
              <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'w-full h-full rounded-full transition-all duration-300',
                    'bg-white border-gray-300' => $model->id != $currentSelection,
                    'bg-green-500 border-green-500' => $model->id ==  $currentSelection
              ]); ?>"></span>
        </button>
    </div>

    <!--[if BLOCK]><![endif]--><?php if(count($model->subs) > 0): ?>
        <ul class="pl-2 pt-1 rounded-lg flex-col flex shadow-sm bg-white"
            x-show="openSubStructure"
            x-transition:enter="transition ease-in-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-y-0 -translate-y-1/2"
            x-transition:enter-end="opacity-100 transform scale-y-100 translate-x-0"
            x-transition:leave="transition ease-in-out duration-300"
            x-transition:leave-start="opacity-100 transform scale-y-100 translate-y-0"
            x-transition:leave-end="opacity-0 transform scale-y-0 -translate-y-1/2"
        >
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $model->subs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $_level_name = strtolower((collect(\App\Enums\StructureEnum::cases())->pluck('name','value')[$sub->level]));
                    $_select_value = ($field == 'structure_id' && $isCoded)
                                   ? $sub->code."{$wordSuffixService->getNumberSuffix($sub->code)} {$_level_name}"
                                   : $sub->name;
                    $isCoded = $isCoded ?: 0;
                ?>
                <?php if (isset($component)) { $__componentOriginal91d6979f2b66c7dd48cd128b9dd939f7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91d6979f2b66c7dd48cd128b9dd939f7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.radio-tree.item','data' => ['isCoded' => $isCoded,'listData' => $listData,'field' => $field,'model' => $sub,'key' => $key,'selectedId' => $currentSelection]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('radio-tree.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['isCoded' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isCoded),'listData' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($listData),'field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field),'model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sub),'key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'selected-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($currentSelection)]); ?>
                    - <?php echo e($_select_value); ?>

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
        </ul>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</li>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/radio-tree/item.blade.php ENDPATH**/ ?>