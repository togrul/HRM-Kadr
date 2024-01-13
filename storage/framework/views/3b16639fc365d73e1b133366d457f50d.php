<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'color' => 'default'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'color' => 'default'
]); ?>
<?php foreach (array_filter(([
    'color' => 'default'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<?php
    $classes = $color == 'default' ? 'bg-gray-100' : 'bg-white';
?>
<div class="hs-tooltip flex items-center">
     <input type="checkbox" <?php echo $attributes->merge(['class' => "hs-tooltip-toggle $classes relative shrink-0 w-[3.25rem] h-7 checked:bg-none checked:bg-blue-600 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 border border-transparent ring-1 ring-transparent focus:border-blue-600 focus:ring-blue-600 ring-offset-white focus:outline-none appearance-none dark:bg-gray-700 dark:checked:bg-blue-600 dark:focus:ring-offset-gray-800
     before:inline-block before:w-6 before:h-6 before:bg-white checked:before:bg-blue-200 before:translate-x-0 checked:before:translate-x-full before:shadow before:rounded-full before:transform before:ring-0 before:transition before:ease-in-out before:duration-200 dark:before:bg-gray-400 dark:checked:before:bg-blue-200"]); ?> id="hs-tooltip-example">
     <label for="hs-tooltip-example" class="text-sm font-medium text-gray-500 ml-3 dark:text-gray-400"><?php echo e($slot); ?></label>
 </div>

 <?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/switch.blade.php ENDPATH**/ ?>