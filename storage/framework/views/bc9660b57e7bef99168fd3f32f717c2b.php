<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
     'type',
     'data'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
     'type',
     'data'
]); ?>
<?php foreach (array_filter(([
     'type',
     'data'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $color = match($type)
     {
          'birthday' => 'green',
          'stock','bar' => 'blue',
          'payment' => 'red'
     }
?>

<div class="flex flex-col w-full">
     <div class="leading-4 space-x-1 space-y-2">
          <div class="flex justify-between items-center w-full">
               <span class="bg-<?php echo e($color); ?>-100 text-xs rounded text-<?php echo e($color); ?>-500 font-medium px-2 py-1">
                    <?php echo e(__($type)); ?>

                </span>
                <span class="text-xs font-medium text-gray-500"><?php echo e($data['create_date']->diffForHumans()); ?></span>
          </div>
        
         <p>
             <span class="font-medium"><?php echo e($data['name']); ?></span>
             <?php if(!empty($data['category'])): ?>
               <span class="font-medium text-gray-500">( <?php echo e(__($data['category'])); ?> )</span>
             <?php endif; ?>
         </p> 
         <?php if($type == 'payment'): ?>
               <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                         <?php echo e($data['title'][0]); ?>:<span class="font-medium text-xs py-1 px-2 rounded text-<?php echo e($color); ?>-500"><?php echo e($data['value'][0]); ?></span>
                    </div>
                    <div class="flex items-center space-x-2">
                         <?php echo e($data['title'][1]); ?>:<span class="font-medium text-xs py-1 px-2 rounded text-<?php echo e($color); ?>-500"><?php echo e($data['value'][1]); ?></span>
                    </div>
               </div>
          <?php else: ?>
            <p><?php echo e($data['title'][0]); ?>:<span class="font-medium text-xs py-1 px-2 rounded text-<?php echo e($color); ?>-500"><?php echo e($data['value'][0]); ?></span></p>
          <?php endif; ?>
          <?php if(!empty($data['text'])): ?>
               <span class="font-medium text-gray-500"><?php echo e($data['text']); ?></span>
          <?php endif; ?>  
     </div>
 </div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/notification/item.blade.php ENDPATH**/ ?>