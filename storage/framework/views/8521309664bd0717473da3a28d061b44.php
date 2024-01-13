<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default'
]); ?>
<?php foreach (array_filter(([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $extraClass = match($mode)
     {
          'default' => 'bg-white',
          'gray' => "bg-gray-100",
          'disabled' => "bg-gray-200"
     };
     $isError = $errors->has($name)?'bg-red-50':'';
?>

<input 
     type="<?php echo e($type); ?>" 
     id="<?php echo e($name); ?>" 
     name="<?php echo e($name); ?>"  
     <?php echo e($disabled ? 'disabled' : ''); ?>

     <?php echo $attributes->merge(['class' => "block border-none font-normal w-full mt-1 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-100 ease-in-out transform {$extraClass} {$isError} "]); ?>


><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/livewire-input.blade.php ENDPATH**/ ?>