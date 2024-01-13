<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'name',
    'specialty',
    'admission_year',
    'graduated_year'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'name',
    'specialty',
    'admission_year',
    'graduated_year'
]); ?>
<?php foreach (array_filter(([
    'name',
    'specialty',
    'admission_year',
    'graduated_year'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div style="padding: 3px;">
    <span><?php echo e($name); ?></span>,
    <span><?php echo e($specialty); ?></span> -
    <span><?php echo e(\Carbon\Carbon::parse($admission_year)->format('d.m.Y')); ?></span> -
    <span><?php echo e(\Carbon\Carbon::parse($graduated_year)->format('d.m.Y')); ?></span>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/education-list.blade.php ENDPATH**/ ?>