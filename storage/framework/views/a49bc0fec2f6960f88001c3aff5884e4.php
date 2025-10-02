<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'active' => false,
     'mode' => 'default'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'active' => false,
     'mode' => 'default'
]); ?>
<?php foreach (array_filter(([
     'active' => false,
     'mode' => 'default'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<li class="<?php echo \Illuminate\Support\Arr::toCssClasses([
     'px-3 py-1 font-medium transition duration-150 ease-in rounded-lg flex justify-center items-center',
     'bg-white shadow-md' =>  $active && $mode == 'default',
     'bg-slate-700 shadow-md' =>  $active && $mode == 'dark',
     'text-black hover:bg-white' => $mode == 'default',
     'text-slate-100 hover:bg-slate-600' => $mode == 'dark'
 ]); ?>">
     <a href="#"
     <?php echo e($attributes->merge(['class' => ''])); ?>

     >
         <?php echo e($slot); ?>

     </a>
 </li>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/filter/item.blade.php ENDPATH**/ ?>