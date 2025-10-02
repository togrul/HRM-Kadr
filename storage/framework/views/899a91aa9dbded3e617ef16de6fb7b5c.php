<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['label', 'id', 'type' => 'text', 'color' => 'light', 'hasIcon' => false]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['label', 'id', 'type' => 'text', 'color' => 'light', 'hasIcon' => false]); ?>
<?php foreach (array_filter((['label', 'id', 'type' => 'text', 'color' => 'light', 'hasIcon' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $extraClass = $color == 'light' ? 'bg-white' : 'bg-slate-900 text-white';
?>

<?php if($hasIcon): ?>
<div class="flex items-center divide-x divide-zinc-300 rounded-2xl border border-zinc-300 py-4 shadow-sm">
    <div class="px-6">
        <?php echo e($slot); ?>

    </div>
    <div class="flex flex-col w-full px-2 <?php echo e($extraClass); ?>">
        <label for="<?php echo e($id); ?>" class="block font-semibold text-sm tracking-tight ml-3 font-title text-zinc-400"><?php echo e($label); ?></label>
        <input id="<?php echo e($id); ?>" name="<?php echo e($id); ?>" type="<?php echo e($type); ?>" <?php echo $attributes->merge([
            'class' => 'py-0 border-none rounded-lg text-sm font-medium focus:ring-0 outline-0 ' . $extraClass,
        ]); ?>

            autocomplete="off">
    </div>
</div>
<?php else: ?>
<div class="flex flex-col <?php echo e($extraClass); ?> px-2 py-3 rounded-2xl border border-zinc-300">
    <label for="<?php echo e($id); ?>" class="block font-semibold text-sm tracking-tight ml-3 font-title text-zinc-400"><?php echo e($label); ?></label>
    <input id="<?php echo e($id); ?>" name="<?php echo e($id); ?>" type="<?php echo e($type); ?>" <?php echo $attributes->merge([
        'class' => 'py-0 border-none rounded-lg text-sm font-medium focus:ring-0 ' . $extraClass,
    ]); ?>

        autocomplete="off">
</div>
<?php endif; ?>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/amazing-input.blade.php ENDPATH**/ ?>