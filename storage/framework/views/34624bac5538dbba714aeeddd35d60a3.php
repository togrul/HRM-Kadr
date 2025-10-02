<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format',
     'script'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format',
     'script'
]); ?>
<?php foreach (array_filter(([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format',
     'script'
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
          'gray' => "bg-gray-100"
     };
     $isError = $errors->has($name)?'bg-red-50':'';

     $format = "Y-MM-DD" ? 'DD.MM.Y' : $format;
     $currentYear = \Carbon\Carbon::now()->format('Y');
?>

<input
    type="<?php echo e($type); ?>"
    id="<?php echo e($name); ?>"
    name="<?php echo e($name); ?>"
    x-data
    x-ref="input"
    x-on:change="$dispatch('input', $el.value)"
    x-init="(function (pikaday, $el) {
          pikaday.defaultDate = $el.value;
          <?php echo e($script ?? ''); ?> ;
          return pikaday;
        })(new Pikaday({
          field: $el,
          format: '<?php echo e($format); ?>',
          yearRange: 100,
          onSelect: function (date) { $el.value = moment(date.toString()).format('<?php echo e($format); ?>'); }
         }), $el)"
    <?php echo e($disabled ? 'disabled' : ''); ?>

    <?php echo $attributes->merge(['class' => "block border-none font-normal w-full mt-1 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-100 ease-in-out transform {$extraClass} {$isError} "]); ?>


>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/pikaday-input.blade.php ENDPATH**/ ?>