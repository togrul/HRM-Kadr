<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'eventToOpenModal' => null,
    'livewireEventToOpenModal' => null,
    'event-to-close-modal',
    'modal-title',
    'modal-confirm-button-text',
    'wire-click'
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'eventToOpenModal' => null,
    'livewireEventToOpenModal' => null,
    'event-to-close-modal',
    'modal-title',
    'modal-confirm-button-text',
    'wire-click'
]); ?>
<?php foreach (array_filter(([
    'eventToOpenModal' => null,
    'livewireEventToOpenModal' => null,
    'event-to-close-modal',
    'modal-title',
    'modal-confirm-button-text',
    'wire-click'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div
    x-init="
        $wire.on('<?php echo e($eventToCloseModal); ?>',() => {
            openDeleteModal = false
        })
        <?php if( $livewireEventToOpenModal): ?>
        $wire.on('<?php echo e($livewireEventToOpenModal); ?>',() => {
            openDeleteModal = true
            $nextTick(() => $refs.confirmButton.focus())
          })
        <?php endif; ?>
        "
    x-data={openDeleteModal:false}
    x-show="openDeleteModal"
    x-transition.opacity
    @keydown.escape.window="openDeleteModal = false"
    <?php if( $eventToOpenModal): ?>
        <?php echo e('@'.$eventToOpenModal); ?>.window="
        openDeleteModal = true
        $nextTick(() => $refs.confirmButton.focus())
    "
    <?php endif; ?>
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    style="display: none;"
>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm">

        <div  x-show="openDeleteModal"
             x-transition
              class="fixed inset-0 transition-opacity bg-neutral-500 bg-opacity-75"
              aria-hidden="true"
        ></div>


        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>


        <div x-show="openDeleteModal"
             x-transition
             class="inline-block text-left align-bottom transition-all transform bg-white rounded-xl sm:my-8 ring-1 ring-black/5 sm:align-middle w-full sm:max-w-xl md:max-w-2xl lg:max-w-4xl overflow-hidden"
        >
            <div class="px-5 py-4 border-b border-neutral-200/70 dark:border-neutral-800/60">
                <div class="flex items-start justify-between gap-4">
                <h2 id="comment-modal-title" class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
                    <?php echo e($modalTitle); ?>

                </h2>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl px-2 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-neutral-400"
                    aria-label="Bağla"
                    @click="openDeleteModal = false"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
            </div>

            <div class="flex flex-col px-4 py-2 space-y-2">
                <?php echo e($slot); ?>

            </div>
           <div class="px-5 py-4 border-t border-neutral-200/70 dark:border-neutral-800/60 flex items-center justify-end gap-1">
                <button wire:click='<?php echo e($wireClick); ?>' x-ref="confirmButton" type="button" class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium bg-rose-500 text-white hover:bg-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:opacity-60">
                    <svg wire:loading class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" fill="currentColor"></path>
                    </svg>
                    <?php echo e($modalConfirmButtonText); ?>

                </button>
                <button @click="openDeleteModal = false" type="button" class="rounded-xl px-4 py-2 text-sm font-medium border border-neutral-200 dark:border-neutral-800 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-300 flex items-center gap-2">
                    <?php echo e(__('Cancel')); ?>

                </button>
            </div>

        </div>
    </div>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/modal-confirm-lg.blade.php ENDPATH**/ ?>