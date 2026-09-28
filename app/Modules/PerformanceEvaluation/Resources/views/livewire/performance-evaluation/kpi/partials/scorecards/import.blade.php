@if ($showImport && ! $card)
    {{-- ───────────── Excel import ───────────── --}}
    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}">
            <p class="text-[13px] font-semibold text-ink">{{ __($t.'.import.title') }}</p>
            <button type="button" wire:click="toggleImport" class="text-[14px] font-medium text-ink-faint hover:text-ink">{{ __($t.'.actions.cancel') }}</button>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-3">
            @foreach ([1, 2, 3] as $step)
                <div class="flex gap-3">
                    <span class="hrm-num flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-[12px] font-semibold text-white">{{ $step }}</span>
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-ink">{{ __($t.'.import.steps.'.$step.'.title') }}</p>
                        <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ __($t.'.import.steps.'.$step.'.body') }}</p>
                        @if ($step === 1)
                            <button type="button" wire:click="downloadActualsTemplate" class="mt-2 inline-flex h-10 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3 text-[14px] font-semibold text-ink-soft hover:border-zinc-300 hover:text-ink">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
                                {{ __($t.'.import.download') }}
                            </button>
                        @elseif ($step === 3)
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 bg-[#fafafa] px-3 text-[14px] font-medium text-ink-soft hover:border-zinc-400">
                                    <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="hidden">
                                    <span class="max-w-[10rem] truncate">{{ $importFile ? $importFile->getClientOriginalName() : __($t.'.import.choose') }}</span>
                                </label>
                                <button type="button" wire:click="importActuals" wire:loading.attr="disabled" wire:target="importActuals,importFile" @disabled(! $importFile) class="inline-flex h-10 items-center rounded-lg bg-ink px-3 text-[14px] font-semibold text-white hover:bg-ink-hover disabled:opacity-40">{{ __($t.'.import.submit') }}</button>
                            </div>
                            @error('importFile') <x-validation>{{ $message }}</x-validation> @enderror
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @if ($importErrors !== [])
            <div class="border-t border-hairline-subtle bg-rose-50/50 px-5 py-3">
                <p class="text-[12.5px] font-semibold text-rose-700">{{ __($t.'.import.failed', ['count' => count($importErrors)]) }}</p>
                <ul class="mt-1.5 max-h-40 space-y-0.5 overflow-y-auto text-[12px] text-rose-700">
                    @foreach ($importErrors as $line => $message)
                        <li><span class="hrm-num font-semibold">{{ __($t.'.import.row', ['row' => $line]) }}</span> — {{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
