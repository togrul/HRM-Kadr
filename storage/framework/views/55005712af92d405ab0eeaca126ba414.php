<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['rows']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['rows']); ?>
<?php foreach (array_filter((['rows']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<tr>
    <td colspan="<?php echo e($rows); ?>">
        <div class="flex flex-col space-y-3 items-center py-6">
            <img src="<?php echo e(asset('assets/images/empty.png')); ?>" class="max-w-full max-h-48 bg-blend-luminosity mix-blend-luminosity" alt="">
            <span class="font-medium text-lg"><?php echo e(__('No information added')); ?></span>
        </div>
    </td>
</tr>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/table/empty.blade.php ENDPATH**/ ?>