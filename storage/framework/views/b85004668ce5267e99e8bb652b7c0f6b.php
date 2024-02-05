<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
'type' => 'success',
'redirect' => false,
'messageToDisplay' => ''
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
'type' => 'success',
'redirect' => false,
'messageToDisplay' => ''
]); ?>
<?php foreach (array_filter(([
'type' => 'success',
'redirect' => false,
'messageToDisplay' => ''
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div x-data="{
                    isActiveNotification: false ,
                    isError: <?php if($type === 'success'): ?> false <?php elseif($type === 'error'): ?> true <?php endif; ?>,
                    messageToDisplay:'<?php echo e($messageToDisplay); ?>',
                    showNotification(message) {
                         this.isActiveNotification = true
                         this.messageToDisplay = message
                         setTimeout(() => {
                              this.isActiveNotification = false
                         },7000)
                    }
               }" x-show.transition.opacity.duration.500="isActiveNotification" x-init="
               <?php if($redirect): ?>
                    $nextTick(() => showNotification(messageToDisplay))
               <?php else: ?>
                    Livewire.on('personnelAdded',message => {
                         isError = false
                         showNotification(message)
                    })
                    Livewire.on('personnelWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('staffAdded',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('staffWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('roleUpdated',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('roleWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('permissionUpdated',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('permissionWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('userAdded',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('userWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('menuAdded',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('fileAdded',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('menuWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('staffScheduleError',message => {
                         isError = true
                         showNotification(message)
                    })

                    Livewire.on('settingsUpdated',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('settingsWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('candidateAdded',message => {
                         isError = false
                         showNotification(message)
                    })

                    Livewire.on('candidateWasDeleted',message => {
                         isError = false
                         showNotification(message)
                    })
               <?php endif; ?>
          "
     class="fixed top-0 right-0 z-[99999] flex justify-between w-full max-w-xs px-6 py-5 mx-2 my-8 bg-white border shadow-lg sm:mx-6 sm:max-w-sm rounded-xl"
     style="display: none;">
     <div class="flex items-center justify-center text-sm font-normal text-gray-500 sm:text-base">

          <div class="w-8 h-8">
               <svg x-show="!isError" class="w-8 h-8 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                         d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
               </svg>
               <svg x-show="isError" class="w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                         d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
               </svg>
          </div>

          <div class="ml-2 text-base" x-html="messageToDisplay">
               
          </div>
     </div>
     <button @click="isActiveNotification=false" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
     </button>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/components/notification.blade.php ENDPATH**/ ?>