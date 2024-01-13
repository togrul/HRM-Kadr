<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'isButton' => false,
     'extraClasses',
     'standartWidth' => false
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'isButton' => false,
     'extraClasses',
     'standartWidth' => false
]); ?>
<?php foreach (array_filter(([
     'isButton' => false,
     'extraClasses',
     'standartWidth' => false
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $extraClasses = $isButton ? 'text-sm font-medium text-right px-3' : 'px-6';
     $extraClasses .= !$standartWidth ? ' whitespace-nowrap':'';
?>

<td <?php echo e($attributes->merge(['class' => "py-4 {$extraClasses}"])); ?>>
     <?php echo e($slot); ?>

</td><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/table/td.blade.php ENDPATH**/ ?>