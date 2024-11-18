<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['headers','divide' => true]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['headers','divide' => true]); ?>
<?php foreach (array_filter((['headers','divide' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<table <?php echo e($attributes->merge(['class' => 'min-w-full divide-y divide-gray-200'])); ?>>
     <thead class="bg-slate-50">
         <tr>
             <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $headers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
               <!--[if BLOCK]><![endif]--><?php if($header != 'action'): ?>
               <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                    <?php echo e($header); ?>

               </th>
               <?php else: ?>
               <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only"><?php echo e(__('Edit')); ?></span>
               </th>
               <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
             <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            
         </tr>
     </thead>
 
     <tbody class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'bg-white',
        'divide-y divide-gray-200' => $divide
     ]); ?>">
         <?php echo e($slot); ?>

     </tbody>
 </table><?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/table/tbl.blade.php ENDPATH**/ ?>