<div class="mt-6 flex flex-wrap gap-2">
    <button
        type="button"
        wire:click="setTargetStage('{{ $this->nextSuggestedStage() }}')"
        @disabled(! $this->stageActionPermissions['transition'])
        class="{{ $this->stageActionPermissions['transition'] ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'cursor-not-allowed bg-zinc-100 text-zinc-400' }} rounded-full border border-zinc-900 px-4 py-2 text-sm font-semibold transition"
    >
        {{ __('candidates::recruitment.actions.move_next_stage') }}: {{ $this->nextStageLabel }}
    </button>
    <button
        type="button"
        wire:click="setTargetStage('rejected')"
        @disabled(! $this->stageActionPermissions['reject'])
        class="{{ $this->stageActionPermissions['reject'] ? 'border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300' : 'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-400' }} rounded-full border px-4 py-2 text-sm font-semibold transition"
    >
        {{ __('candidates::recruitment.actions.reject_application') }}
    </button>
    <button
        type="button"
        wire:click="setTargetStage('{{ $this->finalStageForPack() }}')"
        @disabled(! $this->stageActionPermissions['appoint'])
        class="{{ $this->stageActionPermissions['appoint'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300' : 'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-400' }} rounded-full border px-4 py-2 text-sm font-semibold transition"
    >
        {{ __('candidates::recruitment.actions.finalize_application') }}
    </button>
</div>
