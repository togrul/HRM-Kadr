<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'name',
     'model',
     'value',
     'selected' => false,
     'hidden' => false,
     'checked' => false
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'name',
     'model',
     'value',
     'selected' => false,
     'hidden' => false,
     'checked' => false
]); ?>
<?php foreach (array_filter(([
     'name',
     'model',
     'value',
     'selected' => false,
     'hidden' => false,
     'checked' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $extraClass = $selected || $hidden ? 'text-gray-900' : 'text-gray-500';
?>

<div class="max-w-sm flex">
     <label class="inline-flex items-center cursor-pointer <?php echo e($hidden ? 'line-through' : ''); ?>">
       <input
          <?php echo e($attributes->merge(['class' => "relative w-5 h-5 mr-2 bg-trueGray-100 text-green-400 border border-gray-300 rounded focus:ring-green-500 focus:ring-opacity-25"])); ?>

           wire:model.live="<?php echo e($model); ?>"
           <?php if(!empty($value)): ?>  value="<?php echo e($value); ?>"  <?php endif; ?>
           name="<?php echo e($name); ?>"
           type="checkbox"
            <?php if($checked): ?> <?php if(true): echo 'checked'; endif; ?> <?php endif; ?>
           <?php echo e($hidden ? 'disabled' : ''); ?>

       />
       <span class="text-sm font-medium <?php echo e($extraClass); ?>"><?php echo e($slot); ?></span>
     </label>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/checkbox.blade.php ENDPATH**/ ?>