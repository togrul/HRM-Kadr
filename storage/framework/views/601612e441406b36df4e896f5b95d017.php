<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title' => 'Şərh əlavə et',
    'confirm' => 'Təsdiq et',
    'cancel' => 'İmtina',
    'confirmAction' => 'confirmComment', // default olaraq confirmComment
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title' => 'Şərh əlavə et',
    'confirm' => 'Təsdiq et',
    'cancel' => 'İmtina',
    'confirmAction' => 'confirmComment', // default olaraq confirmComment
]); ?>
<?php foreach (array_filter(([
    'title' => 'Şərh əlavə et',
    'confirm' => 'Təsdiq et',
    'cancel' => 'İmtina',
    'confirmAction' => 'confirmComment', // default olaraq confirmComment
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div
  x-data="{
    open: false,
    busy: false,

    // Alpine state
    action: null,
    leaveId: null,

    // Livewire <-> Alpine sync
    comment: $wire.entangle('comment').live,

    openModal(){ this.open = true; this.$nextTick(() => this.$refs.ta?.focus()) },
    closeModal(){ if (!this.busy) this.open = false },

    handleOpen(e){
      const d = e?.detail ?? {};
      this.action  = d.action ?? null;
      this.leaveId = d.leaveId ?? d.id ?? null; // id gəlirsə dəstəklə
      this.openModal();
    },

    async submit(){
      if (this.busy) return;
      this.busy = true;
      try {
        // Birbaşa bu komponentin metodunu çağır (instansiya dəqiqdir)
        await $wire.call('<?php echo e($confirmAction); ?>', this.action, this.leaveId);
      } catch (e) {
        console.error(e);
        this.$dispatch('toast', { type: 'error', message: 'Xəta baş verdi' });
      } finally {
        this.busy = false;
        this.comment = ''; // entangle sayəsində Livewire-da da sıfırlanır
        this.closeModal();
      }
    }
  }"

  
  x-on:comment:open.window="handleOpen($event)"
  x-on:setOpenComment.window="handleOpen($event)"
  x-on:set-open-comment.window="handleOpen($event)"

  x-on:close-comment.window="closeModal()"
  x-on:closeComment.window="closeModal()"

  class="relative"
>
  <template x-teleport="body">
    <div
      x-show="open"
      wire:ignore
      x-transition.opacity
      class="fixed inset-0 z-50 flex items-center justify-center"
      aria-labelledby="comment-modal-title"
      aria-modal="true"
      role="dialog"
    >
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()" aria-hidden="true"></div>

      <div x-show="open" x-transition class="relative z-10 w-full max-w-lg mx-4">
        <div class="rounded-2xl bg-white shadow-xl ring-1 ring-black/5 dark:bg-neutral-900">
          <div class="px-5 py-4 border-b border-neutral-200/70 dark:border-neutral-800/60">
            <div class="flex items-start justify-between gap-4">
              <h2 id="comment-modal-title" class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                <?php echo e(__($title)); ?>

              </h2>
              <button type="button" class="inline-flex items-center justify-center rounded-xl px-2 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-neutral-400"
                      @click="closeModal()" aria-label="Bağla">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="px-5 pt-4 pb-2">
            <?php echo e($slot); ?>

          </div>

          <div class="px-5 py-4 border-t border-neutral-200/70 dark:border-neutral-800/60 flex items-center justify-end gap-3">
            <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium border border-neutral-200 dark:border-neutral-800 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-300"
                    @click="closeModal()" :disabled="busy">
              <?php echo e(__($cancel)); ?>

            </button>

            <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 disabled:opacity-60"
                    @click="submit()"
                    :disabled="busy || !String(comment ?? '').trim().length">
                    <span x-show="!busy"><?php echo e(__($confirm)); ?></span>
                    <span x-show="busy" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" fill="currentColor"></path>
                    </svg>
                <?php echo e(__('Loading')); ?>...
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div x-show="open" x-on:keydown.window.escape.prevent="closeModal()"></div>
  </template>
</div>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/components/ui/confirmation-modal.blade.php ENDPATH**/ ?>