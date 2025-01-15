<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'startDate',
    'endDate',
    'color'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'startDate',
    'endDate',
    'color'
]); ?>
<?php foreach (array_filter(([
    'startDate',
    'endDate',
    'color'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $duration = $startDate->diffInDays($endDate);
    $currentProgress = $startDate->diffInDays(\Carbon\Carbon::now()->addDay());
    $percentage = ($duration > 0) ? ($currentProgress / $duration) * 100 : 0;
?>

<div class="flex flex-col py-1 px-1">
    <div class="flex items-center justify-between space-x-2">
        <span class="text-xs text-gray-400">
            <?php echo e($startDate->format('d.m.Y')); ?>

        </span>
        <span class="text-xs text-<?php echo e($color); ?>-500">
            <?php echo e($slot); ?>

        </span>
        <span class="text-xs text-gray-800">
            <?php echo e($endDate->format('d.m.Y')); ?>

        </span>
    </div>
    <div class="w-full h-1.5 bg-<?php echo e($color); ?>-100 rounded-lg relative overflow-hidden">
        <span class="bg-<?php echo e($color); ?>-500 absolute left-0 top-0 h-full" style="width:<?php echo e(number_format($percentage, 2)); ?>%;"></span>
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/progress.blade.php ENDPATH**/ ?>