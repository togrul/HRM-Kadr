<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'label',
     'id',
     'type' => 'text',
     'color' => 'light'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'label',
     'id',
     'type' => 'text',
     'color' => 'light'
]); ?>
<?php foreach (array_filter(([
     'label',
     'id',
     'type' => 'text',
     'color' => 'light'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $extraClass = $color == 'light' ? 'bg-slate-100' : 'bg-slate-900 text-white';
?>

<div class="flex flex-col <?php echo e($color == 'light' ? 'bg-slate-100' : 'bg-slate-900'); ?> px-2 py-3 rounded-xl">
     <label for="<?php echo e($id); ?>" class="block font-medium text-sm ml-3 text-gray-400"><?php echo e($label); ?></label>
     <input id="<?php echo e($id); ?>" name="<?php echo e($id); ?>" type="<?php echo e($type); ?>" <?php echo $attributes->merge(['class' =>  'py-0 border-none rounded-lg text-sm font-medium focus:ring-0 outline-none px-3 ' . $extraClass]); ?> autocomplete="off">
 </div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/amazing-input.blade.php ENDPATH**/ ?>