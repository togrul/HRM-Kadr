<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'mode' => 'default',
    'disabled' => false,
    'type' => 'button'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'mode' => 'default',
    'disabled' => false,
    'type' => 'button'
]); ?>
<?php foreach (array_filter(([
    'mode' => 'default',
    'disabled' => false,
    'type' => 'button'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $extraClasses = match($mode)
    {
        'default' => 'bg-white hover:bg-gray-200 active:bg-gray-200 focus:border-gray-200 text-gray-600',
        'gray' => 'bg-gray-100 hover:bg-gray-200 active:bg-gray-200 focus:border-gray-200 text-gray-600',
        'primary' => 'bg-blue-500 hover:bg-blue-600 active:bg-blue-500 focus:border-blue-500 text-white',
        'success' => 'bg-green-500 hover:bg-green-600 active:bg-green-500 focus:border-green-500 text-white',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 active:bg-yellow-500 focus:border-yellow-500 text-white',
        'danger' => 'bg-red-500 hover:bg-red-600 active:bg-red-500 focus:border-red-500 text-white',
        'light-green' => 'bg-green-100 border border-green-500 hover:bg-green-200 active:bg-green-100 focus:bg-green-100 text-green-500',
        'teal' => 'bg-teal-100 border border-teal-500 hover:bg-teal-200 active:bg-teal-100 focus:bg-teal-100 text-teal-500',
        'slate' => 'bg-slate-100 border border-slate-500 hover:bg-slate-200 active:bg-slate-100 focus:bg-slate-100 text-slate-500',
        'light-red' => 'bg-red-100 border border-red-500 hover:bg-red-200 active:bg-red-100 focus:bg-red-100 text-red-500',
        'light-blue' => 'bg-blue-100 border border-blue-500 hover:bg-blue-200 active:bg-blue-100 focus:bg-blue-100 text-blue-500',
        'black' => 'bg-black border border-gray-900 hover:bg-gray-800 active:bg-gray-800 focus:bg-gray-800 text-gray-100',
        'rose' => 'bg-rose-50 border border-rose-500 hover:bg-rose-100 active:bg-rose-100 focus:bg-rose-100 text-rose-500',
    }
?>

<?php if($type == 'button'): ?>
    <button <?php if($disabled): echo 'disabled'; endif; ?> <?php echo e($attributes->merge(['type' => 'submit', 'class' => 'camelcase inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl font-semibold shadow-sm text-sm focus:outline-none focus:ring ring-gray-300 disabled:opacity-25 transition-all ease-in-out duration-150 '.$extraClasses])); ?>>
        <?php echo e($slot); ?>

    </button>
<?php else: ?>
    <a <?php echo e($attributes->merge(['type' => 'submit', 'class' => 'camelcase inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl font-semibold shadow-sm text-sm focus:outline-none focus:ring ring-gray-300 disabled:opacity-25 transition-all ease-in-out duration-150 '.$extraClasses])); ?>>
        <?php echo e($slot); ?>

    </a>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/button.blade.php ENDPATH**/ ?>