<div class="flex flex-col space-y-2 px-2 py-3">
    <button wire:click.prevent="selectService('general')" 
    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
        'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != 'general',
        'text-white bg-emerald-500' => $selectedService == 'general'
    ]); ?>">
    <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
        </svg>                   
    </div>
     
    <span class="text-sm"><?php echo e(__('General')); ?></span>
</button>
    <button wire:click.prevent="selectService('menus')" 
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
            'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != 'menus',
            'text-white bg-emerald-500' => $selectedService == 'menus'
        ]); ?>">
        <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z" />
            </svg>              
        </div>
         
        <span class="text-sm"><?php echo e(__('Menus')); ?></span>
   </button>
   <button wire:click.prevent="selectService('roles')" 
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
            'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != 'roles',
            'text-white bg-emerald-500' => $selectedService == 'roles'
        ]); ?>">
        <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>     
        </div>
         
        <span class="text-sm"><?php echo e(__('Roles and permissions')); ?></span>
   </button>
      <button wire:click.prevent="selectService('users')" 
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
            'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != 'users',
            'text-white bg-emerald-500' => $selectedService == 'users'
        ]); ?>">
           <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>     
           </div>
     
        <span class="text-sm"><?php echo e(__('Users')); ?></span>
   </button>
   <button wire:click.prevent="selectService('ranks')" 
   class="<?php echo \Illuminate\Support\Arr::toCssClasses([
       'appearance-none space-x-2 rounded-xl transition-all duration-300 font-medium px-6 py-3 flex justify-start items-center',
       'text-slate-800 bg-gray-50 hover:bg-emerald-100' => $selectedService != 'ranks',
       'text-white bg-emerald-500' => $selectedService == 'ranks'
   ]); ?>">
      <div class="flex justify-center items-center p-2 rounded-xl bg-emerald-100 text-emerald-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l7.5-7.5 7.5 7.5m-15 6l7.5-7.5 7.5 7.5" />
        </svg>          
      </div>

   <span class="text-sm"><?php echo e(__('Ranks')); ?></span>
</button>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/livewire/structure/services.blade.php ENDPATH**/ ?>