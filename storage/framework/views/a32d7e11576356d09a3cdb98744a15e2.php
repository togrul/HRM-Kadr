<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['inputName','auto' => true]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['inputName','auto' => true]); ?>
<?php foreach (array_filter((['inputName','auto' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php $__env->startPush('css'); ?>
     <link rel="stylesheet" type="text/css" href="<?php echo e(asset('assets/css/pikaday.min.css')); ?>">
<?php $__env->stopPush(); ?>
<?php $__env->startPush('js'); ?>
     <script src="<?php echo e(asset('assets/js/moment.min.js')); ?>"></script>
     <script src="<?php echo e(asset('assets/js/pikaday.min.js')); ?>"></script>
     <!--[if BLOCK]><![endif]--><?php if($auto): ?>
     <script>
          new Pikaday ({
               field: document.getElementById('date'),
               onSelect: function(){
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('<?php echo e($inputName); ?>',this.getMoment().format('Y-MM-DD'));
               }
          })
     </script>
     <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
<?php $__env->stopPush(); ?><?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/datepicker.blade.php ENDPATH**/ ?>