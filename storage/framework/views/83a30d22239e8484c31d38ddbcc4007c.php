<?php ($uid = 'selgen-'.\Illuminate\Support\Str::random(6)); ?>

<div x-data="{ open: <?php if ((object) ('open') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('open'->value()); ?>')<?php echo e('open'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('open'); ?>')<?php endif; ?> }" @click.outside="open = false" class="w-full relative">
    
    <button type="button"
            class="relative w-full py-2 pl-3 pr-10 text-left rounded-lg shadow-sm cursor-default
                   bg-neutral-100 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            :aria-expanded="open"
            aria-controls="<?php echo e($uid); ?>-listbox"
            @click="open = !open"
            @keydown.arrow-down.prevent="open = true"
            @keydown.space.prevent="open = !open"
            @keydown.enter.prevent="open = !open">
        <span class="flex items-center">
            <span class="block ml-1 font-normal text-neutral-900 truncate"><?php echo e($this->selectedLabel); ?></span>
        </span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
            <svg class="w-5 h-5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </span>
    </button>

    
    <div x-show="open" x-transition.opacity.duration.100ms x-cloak
         id="<?php echo e($uid); ?>-listbox"
         class="absolute z-50 w-full px-3 py-2 mt-1 space-y-2 overflow-auto text-base bg-white rounded-md shadow-xl max-h-56 focus:outline-none sm:text-sm">

        
        <input type="text"
               class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
               placeholder="<?php echo e(__('Search...')); ?>"
               wire:model.live.debounce.250ms="search"
               @keydown.escape.stop="open = false"
               @click.stop="open = true"
               @keydown.stop = "open = true"
            />

        
        <div class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-100 rounded-lg"
             wire:click="select(null)">
            <span class="block ml-1 truncate text-neutral-700"><?php echo e($placeholder ?? '---'); ?></span>
        </div>

        
        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $this->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div wire:key="opt-<?php echo e($opt['id']); ?>"
                 class="group relative py-2 pl-3 pr-9 cursor-pointer select-none hover:bg-blue-100 rounded-lg <?php echo e((string)$model === $opt['id'] ? 'bg-blue-50' : 'bg-neutral-50'); ?>"
                 wire:click="select('<?php echo e($opt['id']); ?>')">
                <span class="block ml-1 truncate text-neutral-800"><?php echo e($opt['label']); ?></span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="py-2 px-3 text-sm text-neutral-500 select-none"><?php echo e(__('No results')); ?></div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/livewire/fields/select-generic.blade.php ENDPATH**/ ?>