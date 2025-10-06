<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'label' => null,
    'searchModel',              // e.g. "personSearch"
    'selected' => null,         // e.g. ['id' => 1, 'name' => 'Jane'] or null
    'displayKey' => 'name',
    'idKey' => 'id',
    'onClear' => null,          // e.g. "clearPerson"
    'placeholder' => '',
    'clearField' => null,       // optional: pass a field name instead of id
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'label' => null,
    'searchModel',              // e.g. "personSearch"
    'selected' => null,         // e.g. ['id' => 1, 'name' => 'Jane'] or null
    'displayKey' => 'name',
    'idKey' => 'id',
    'onClear' => null,          // e.g. "clearPerson"
    'placeholder' => '',
    'clearField' => null,       // optional: pass a field name instead of id
]); ?>
<?php foreach (array_filter(([
    'label' => null,
    'searchModel',              // e.g. "personSearch"
    'selected' => null,         // e.g. ['id' => 1, 'name' => 'Jane'] or null
    'displayKey' => 'name',
    'idKey' => 'id',
    'onClear' => null,          // e.g. "clearPerson"
    'placeholder' => '',
    'clearField' => null,       // optional: pass a field name instead of id
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $inputId = $attributes->get('id', $searchModel);
    $listboxId = $inputId . '_listbox';
?>

<div x-data="{ open: false }" class="flex flex-col relative">
    <!--[if BLOCK]><![endif]--><?php if(!empty($label)): ?>
        <?php if (isset($component)) { $__componentOriginald8ba2b4c22a13c55321e34443c386276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8ba2b4c22a13c55321e34443c386276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.label','data' => ['for' => ''.e($inputId).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => ''.e($inputId).'']); ?><?php echo e(__($label)); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $attributes = $__attributesOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__attributesOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8ba2b4c22a13c55321e34443c386276)): ?>
<?php $component = $__componentOriginald8ba2b4c22a13c55321e34443c386276; ?>
<?php unset($__componentOriginald8ba2b4c22a13c55321e34443c386276); ?>
<?php endif; ?>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!--[if BLOCK]><![endif]--><?php if($selected): ?>
        <div class="flex items-center gap-2">
            <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['id' => ''.e($inputId).'_display','name' => ''.e($inputId).'_display','mode' => 'gray','class' => 'flex-auto','value' => $selected[$displayKey] ?? '','readonly' => true,'disabled' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => ''.e($inputId).'_display','name' => ''.e($inputId).'_display','mode' => 'gray','class' => 'flex-auto','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selected[$displayKey] ?? ''),'readonly' => true,'disabled' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $attributes = $__attributesOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__attributesOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9364c0b92ee5ab519273634c79f86a27)): ?>
<?php $component = $__componentOriginal9364c0b92ee5ab519273634c79f86a27; ?>
<?php unset($__componentOriginal9364c0b92ee5ab519273634c79f86a27); ?>
<?php endif; ?>
            <!--[if BLOCK]><![endif]--><?php if($onClear): ?>
                <?php
                    $clearPayload = !is_null($clearField)
                        ? json_encode($clearField)
                        : json_encode($selected[$idKey] ?? null);
                ?>
                <button type="button" class="appearance-none flex-none w-max" wire:click="<?php echo e($onClear); ?>(<?php echo e($clearPayload); ?>)">
                    <?php if (isset($component)) { $__componentOriginalf0c6472a6fe5dd1eb97710caff505d07 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.close-icon','data' => ['color' => 'text-rose-600','size' => 'w-8 h-8','hover' => 'text-rose-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.close-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-rose-600','size' => 'w-8 h-8','hover' => 'text-rose-700']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07)): ?>
<?php $attributes = $__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07; ?>
<?php unset($__attributesOriginalf0c6472a6fe5dd1eb97710caff505d07); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c6472a6fe5dd1eb97710caff505d07)): ?>
<?php $component = $__componentOriginalf0c6472a6fe5dd1eb97710caff505d07; ?>
<?php unset($__componentOriginalf0c6472a6fe5dd1eb97710caff505d07); ?>
<?php endif; ?>
                </button>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal9364c0b92ee5ab519273634c79f86a27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9364c0b92ee5ab519273634c79f86a27 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.livewire-input','data' => ['id' => ''.e($inputId).'','mode' => 'gray','name' => ''.e($searchModel).'','placeholder' => ''.e($placeholder).'','wire:model.live.debounce.300ms' => ''.e($searchModel).'','role' => 'combobox','ariaAutocomplete' => 'list','ariaControls' => ''.e($listboxId).'','xBind:ariaExpanded' => 'open.toString()','@click.stop' => 'open = true','@keydown.escape.stop' => 'open = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('livewire-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => ''.e($inputId).'','mode' => 'gray','name' => ''.e($searchModel).'','placeholder' => ''.e($placeholder).'','wire:model.live.debounce.300ms' => ''.e($searchModel).'','role' => 'combobox','aria-autocomplete' => 'list','aria-controls' => ''.e($listboxId).'','x-bind:aria-expanded' => 'open.toString()','@click.stop' => 'open = true','@keydown.escape.stop' => 'open = false']); ?>
<?php echo $__env->renderComponent(); ?>
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

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.scale
        @click.outside="open = false"
        @click.away="open = false"
        @mousedown.outside="open = false"
        id="<?php echo e($listboxId); ?>"
        role="listbox"
        class="absolute z-[99] top-[60px] left-0 w-full px-1 py-2 bg-neutral-50 rounded-lg border border-neutral-200 drop-shadow-md flex flex-col max-h-40 overflow-y-auto"
    >
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/ui/search-input-select.blade.php ENDPATH**/ ?>