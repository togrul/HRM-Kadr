<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['notification']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['notification']); ?>
<?php foreach (array_filter((['notification']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
     $data = $notification->data;
     $type = $data['type'];
     $action = $data['action'] ?? '';
     $isRead = !empty($notification->read_at);
     switch ($action) {
            case 'create':
                $color = 'teal';
                $message = __('has created new personnel');
                $category = __('New '. strtolower($type));
                $addedBy = $data['added_by'];
                break;
            case 'delete':
                $color = 'rose';
                $message = __('has deleted personnel');
                $category = __($type . ' deleted');
                $addedBy = $data['added_by'];
                break;
            case 'birthday':
                $color = 'blue';
                $message = '';
                $category = $type;
                $addedBy = null;
                break;
            default:
                 $color = 'gray';
                 $message = __('has a notification');
        };
?>
<div class="border-t-8 border-gray-50 px-8 py-4 relative">
     <div class="flex items-center justify-between space-x-2">
          <div class="flex items-center space-x-3">
               <span class="rounded-tl-lg rounded-br-lg px-2 py-1 text-sm font-medium bg-<?php echo e($color); ?>-100 text-<?php echo e($color); ?>-500">
                  <?php echo e(__($category)); ?>

               </span>
               <p class="text-base flex items-center space-x-1">
                   <!--[if BLOCK]><![endif]--><?php if($action == 'birthday'): ?>
                       <?php if (isset($component)) { $__componentOriginale2791dd75f39941788bfda38ee9a2f8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale2791dd75f39941788bfda38ee9a2f8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.cake-icon','data' => ['color' => 'text-yellow-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.cake-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'text-yellow-800']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale2791dd75f39941788bfda38ee9a2f8b)): ?>
<?php $attributes = $__attributesOriginale2791dd75f39941788bfda38ee9a2f8b; ?>
<?php unset($__attributesOriginale2791dd75f39941788bfda38ee9a2f8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale2791dd75f39941788bfda38ee9a2f8b)): ?>
<?php $component = $__componentOriginale2791dd75f39941788bfda38ee9a2f8b; ?>
<?php unset($__componentOriginale2791dd75f39941788bfda38ee9a2f8b); ?>
<?php endif; ?>
                       <span class="text-black text-base flex items-center">
                           <span class="text-sm text-gray-500"><?php echo e(__('Age')); ?>:</span><?php echo e(\Carbon\Carbon::parse($data['added_by'])->age); ?>

                       </span>
                   <?php else: ?>
                       <span class="font-medium text-slate-500"><?php echo e($addedBy); ?></span>
                   <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                   <span><?php echo e($message ?? ''); ?> -</span>
                   <span class="font-medium text-sky-500"><?php echo e($data['name']); ?></span>
                </p>
          </div>
         <div class="flex items-center space-x-2">
             <span class="font-light text-sm text-gray-900"><?php echo e($notification->created_at->format('d.m.Y H:i')); ?></span>
             <span class="font-light text-sm text-gray-500">(<?php echo e($notification->created_at->diffForHumans()); ?>)</span>
         </div>
     </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/notification/list-item.blade.php ENDPATH**/ ?>