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
<div class="hidden border-red-200 border-blue-200 border-green-200"></div>
<div class="bg-white border-2 border-<?php echo e($color); ?>-200 shadow-sm rounded-lg px-8 pl-20 py-4 relative">
     <span class="absolute top-0 left-[-1px] rounded-tl-lg rounded-br-lg px-2 py-1 text-xs font-medium bg-<?php echo e($color); ?>-100 text-<?php echo e($color); ?>-500">
          <?php echo e(__($type)); ?>

     </span>

     <div class="flex items-center justify-between space-x-2">
          <div class="flex items-center space-x-3">
               <p class="text-sm">
                    <span class="font-medium"><?php echo e($data['name']); ?></span>
                    <?php if(!empty($data['category'])): ?>
                      <span class="font-medium text-gray-500">( <?php echo e(__($data['category'])); ?> )</span>
                    <?php endif; ?>
                </p> 
                <?php if($type == 'payment'): ?>
               <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-2 text-xs">
                         <span><?php echo e($data['title'][0]); ?></span>:<span class="font-medium text-xs text-<?php echo e($color); ?>-500"><?php echo e($data['value'][0]); ?></span>
                    </div>
                    <div class="flex items-center space-x-2 text-xs">
                        <span><?php echo e($data['title'][1]); ?></span>:<span class="font-medium text-xs text-<?php echo e($color); ?>-500"><?php echo e($data['value'][1]); ?></span>
                    </div>
               </div>
               <?php else: ?>
                    <p class="text-sm"><?php echo e($data['title'][0]); ?>:<span class="font-medium text-xs py-1 px-2 rounded text-<?php echo e($color); ?>-500"><?php echo e($data['value'][0]); ?></span></p>
               <?php endif; ?>
               <?php if(!empty($data['text'])): ?>
                    <span class="font-normal text-sm text-gray-500"><?php echo e($data['text']); ?></span>
               <?php endif; ?>  
          </div>
          <span class="text-xs font-medium text-gray-500"><?php echo e($data['create_date']->diffForHumans()); ?></span>
     </div>
</div> <?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/notification/list-item.blade.php ENDPATH**/ ?>