<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['key','selectedService','title']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['key','selectedService','title']); ?>
<?php foreach (array_filter((['key','selectedService','title']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<button wire:click.prevent="selectService('<?php echo e($key); ?>')"
    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
        'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != $key,
        'text-white bg-emerald-500' => $selectedService == $key
    ]); ?>">
    <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
        <?php echo e($slot); ?>

    </div>

    <span class="text-sm"><?php echo e($title ?? ''); ?></span>
</button>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/services-menu-item.blade.php ENDPATH**/ ?>