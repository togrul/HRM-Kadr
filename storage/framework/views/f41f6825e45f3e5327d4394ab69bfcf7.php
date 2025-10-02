<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'disabled' => false,
     'name',
     'mode' => 'default',
     'placeholder'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'disabled' => false,
     'name',
     'mode' => 'default',
     'placeholder'
]); ?>
<?php foreach (array_filter(([
     'disabled' => false,
     'name',
     'mode' => 'default',
     'placeholder'
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
          'gray' => 'bg-gray-100'
     };
     $isError = $errors->has($name)?'bg-red-50':'';
?>

<textarea 
     id="<?php echo e($name); ?>" 
     rows="3" 
     <?php echo e($disabled ? 'disabled' : ''); ?>

     <?php echo $attributes->merge(['class' => 'p-2.5 w-full border-none mt-1 rounded-lg shadow-sm text-sm font-normal text-gray-900 block focus:ring-blue-500 focus:border-blue-500 '.$extraClass]); ?>

     placeholder="<?php echo e($placeholder); ?>">
</textarea>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/textarea.blade.php ENDPATH**/ ?>