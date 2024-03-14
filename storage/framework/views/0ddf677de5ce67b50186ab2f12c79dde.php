<div x-data="{ isOpen: false }"
     class="fixed inset-0 z-50 overflow-hidden"
     aria-labelledby="slide-over-title"
     role="dialog"
     aria-modal="true"
     x-show="isOpen"
     @keydown.escape.window="isOpen = false;$wire.dispatch('closeSideMenu');document.body.classList.remove('overflow-hidden');"
     x-init="
      <?php
        $arrEvents = ['personnelAdded','permissionSet','staffAdded','userAdded','menuAdded','fileAdded','candidateAdded','templateAdded','componentAdded','orderAdded'];
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

         <div class="relative w-screen md:max-w-3xl lg:max-w-4xl"
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
             <button @click="isOpen=false;$wire.call('closeSideMenu');document.body.classList.remove('overflow-hidden')" class="z-20 p-1 text-white rounded-lg bg-gray-300 hover:text-white hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-white">
               <span class="sr-only"><?php echo e(__('Close')); ?></span>
               <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
               </svg>
             </button>
           </div>

           <div class="flex flex-col h-full py-6 overflow-y-scroll bg-white shadow-xl rounded-tl-2xl rounded-bl-2xl">

             <div class="relative flex-1 px-4 sm:px-6" wire:loading.remove>
              <?php echo e($slot); ?>

             </div>
             <div class="relative flex-1 px-4 sm:px-6" wire:loading>
                <div class="flex flex-col items-center justify-center w-full h-full">
                  <h1 class="text-2xl font-medium uppercase"><?php echo e(__('Loading')); ?>...</h1>
                  <?php if (isset($component)) { $__componentOriginal71c6471fa76ce19017edc287b6f4508c = $component; } ?>
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
<?php if (isset($__componentOriginal71c6471fa76ce19017edc287b6f4508c)): ?>
<?php $component = $__componentOriginal71c6471fa76ce19017edc287b6f4508c; ?>
<?php unset($__componentOriginal71c6471fa76ce19017edc287b6f4508c); ?>
<?php endif; ?>
                </div>
             </div>

           </div>
         </div>
       </div>
     </div>
   </div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/side-modal.blade.php ENDPATH**/ ?>