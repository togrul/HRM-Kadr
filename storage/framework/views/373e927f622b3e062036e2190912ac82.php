<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'size' => 'large'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'size' => 'large'
]); ?>
<?php foreach (array_filter(([
    'size' => 'large'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $sizeClass = match($size){
        'large' => 'md:max-w-3xl lg:max-w-4xl',
        'x-large' => 'md:max-w-4xl lg:max-w-5xl',
        'xx-large' => 'md:max-w-5xl lg:max-w-6xl'
    };
?>

<div x-data="{ isOpen: false }"
     class="fixed inset-0 z-50 overflow-hidden"
     aria-labelledby="slide-over-title"
     role="dialog"
     aria-modal="true"
     x-show="isOpen"
     @keydown.escape.window="isOpen = false;$wire.dispatch('closeSideMenu');document.body.classList.remove('overflow-hidden');"
     x-init="
      <?php
        $arrEvents = ['personnelAdded','permissionSet','staffAdded','userAdded','menuAdded','fileAdded','candidateAdded','templateAdded','componentAdded','orderAdded','rankAdded'];
      ?>
          $wire.on('openSideMenu',() => {
               isOpen = true
               document.body.classList.add('overflow-hidden')
          })
          <?php $__currentLoopData = $arrEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          $wire.on('<?php echo e($event); ?>',() => {
            isOpen = false
            $wire.dispatch('closeSideMenu')
            document.body.classList.remove('overflow-hidden')
          })
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     "
     style="display: none;margin-top:0 !important"
>
     <div class="absolute inset-0 overflow-hidden">

       <div
          class="absolute inset-0 transition-opacity bg-gray-500 bg-opacity-75"
          aria-hidden="true"
          x-show="isOpen"
          x-transition:enter="transition ease-in-out duration-500"
          x-transition:enter-start="transform opacity-0"
          x-transition:enter-end="transform opacity-100"
          x-transition:leave="transition ease-in-out duration-500"
          x-transition:leave-start="transform opacity-100"
          x-transition:leave-end="transform opacity-0"
          style="display: none;"
     ></div>

       <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">

         <div class="relative w-screen <?php echo e($sizeClass); ?>"
               x-show="isOpen"
               x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
               x-transition:enter-start="transform translate-x-full"
               x-transition:enter-end="transform translate-x-0"
               x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
               x-transition:leave-start="transform translate-x-0"
               x-transition:leave-end="transform translate-x-full"
               style="display: none;"
         >

           <div class="absolute top-0 right-0 flex pt-5 pr-2 sm:pr-4"
               x-show="isOpen"
               x-transition:enter="transition ease-in-out duration-500"
               x-transition:enter-start="transform opacity-0"
               x-transition:enter-end="transform opacity-100"
               x-transition:leave="transition ease-in-out duration-500"
               x-transition:leave-start="transform opacity-100"
               x-transition:leave-end="transform opacity-0"
               style="display: none;"
           >
             <button @click="isOpen=false;$wire.call('closeSideMenu');document.body.classList.remove('overflow-hidden')" class="z-20 p-1 text-white rounded-lg hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
               <span class="sr-only"><?php echo e(__('Close')); ?></span>
                 <?php if (isset($component)) { $__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icons.remove-icon','data' => ['size' => 'w-7 h-7','color' => 'text-slate-500','hover' => 'text-slate-900']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('icons.remove-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'w-7 h-7','color' => 'text-slate-500','hover' => 'text-slate-900']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc)): ?>
<?php $attributes = $__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc; ?>
<?php unset($__attributesOriginal2b723b42d6712f974b6e7dfc4c0d88fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc)): ?>
<?php $component = $__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc; ?>
<?php unset($__componentOriginal2b723b42d6712f974b6e7dfc4c0d88fc); ?>
<?php endif; ?>
             </button>
           </div>

           <div class="flex flex-col h-full py-6 overflow-y-scroll bg-white shadow-xl rounded-tl-2xl rounded-bl-2xl">

             <div class="relative flex-1 px-4 sm:px-6" wire:loading.remove>
              <?php echo e($slot); ?>

             </div>
             <div class="relative flex-1 px-4 sm:px-6" wire:loading>
                <div class="flex flex-col items-center justify-center w-full h-full">
                  <h1 class="text-2xl font-medium uppercase"><?php echo e(__('Loading')); ?>...</h1>
                  <?php if (isset($component)) { $__componentOriginal33b2ed0096b8c040ff6141dec9ebf0ba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33b2ed0096b8c040ff6141dec9ebf0ba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-loading','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal-loading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33b2ed0096b8c040ff6141dec9ebf0ba)): ?>
<?php $attributes = $__attributesOriginal33b2ed0096b8c040ff6141dec9ebf0ba; ?>
<?php unset($__attributesOriginal33b2ed0096b8c040ff6141dec9ebf0ba); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33b2ed0096b8c040ff6141dec9ebf0ba)): ?>
<?php $component = $__componentOriginal33b2ed0096b8c040ff6141dec9ebf0ba; ?>
<?php unset($__componentOriginal33b2ed0096b8c040ff6141dec9ebf0ba); ?>
<?php endif; ?>
                </div>
             </div>

           </div>
         </div>
       </div>
     </div>
   </div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/side-modal.blade.php ENDPATH**/ ?>