    @if ($activeTab === 'tests')
        @php
            $d = 'performance_evaluation::dashboard';
            $testsTabs = [
                'banks' => ['label' => __($d.'.tests_subtabs.banks'), 'hint' => __($d.'.cards.test_bank_setup')],
                'questions' => ['label' => __($d.'.tests_subtabs.questions'), 'hint' => __($d.'.cards.test_question_setup')],
                'import' => ['label' => __($d.'.tests_subtabs.import'), 'hint' => __($d.'.cards.test_question_import')],
                'sessions' => ['label' => __($d.'.tests_subtabs.sessions'), 'hint' => __($d.'.cards.test_session_setup')],
                'review' => ['label' => __($d.'.tests_subtabs.review'), 'hint' => __($d.'.cards.open_answer_review')],
            ];
            $section = 'rounded-2xl border border-hairline bg-white shadow-card';
            $sectionHead = 'flex items-center justify-between gap-3 rounded-t-2xl border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
            $textarea = 'w-full rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none focus:ring-0';
            $checkbox = 'h-4 w-4 rounded border-zinc-300 text-ink focus:ring-zinc-400';
            $primary = 'inline-flex h-10 items-center justify-center rounded-xl bg-ink px-5 text-[13px] font-semibold text-white transition hover:bg-ink-hover';
            $secondary = 'inline-flex h-10 items-center justify-center rounded-xl border border-hairline bg-white px-4 text-[13px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink';
            $note = 'rounded-xl bg-[#fafafa] px-3.5 py-3';
            $workspaceLinks = [
                'personnel_links' => route('performance-evaluation.user-personnel-links', ['return' => route('performance-evaluation', ['tab' => 'tests'])]),
                'test_workspace' => route('performance-evaluation.test-workspace', ['return' => route('performance-evaluation', ['tab' => 'tests'])]),
            ];
        @endphp

        <div class="flex flex-col gap-4">
            {{-- ───────────── sub-sections ───────────── --}}
            <div class="hrm-scroll-hidden overflow-x-auto">
                <div class="grid min-w-[640px] grid-cols-5 gap-1 rounded-2xl border border-hairline bg-white p-1 shadow-card">
                    @foreach ($testsTabs as $testsView => $tabMeta)
                        <button type="button"
                            wire:key="tests-subtab-{{ $testsView }}"
                            wire:click.prevent="switchTestsSubTab('{{ $testsView }}')"
                            @class([
                                'flex min-w-0 flex-col items-start rounded-xl px-3.5 py-2.5 text-left transition',
                                'bg-ink text-white' => $testsSubTab === $testsView,
                                'text-ink-muted hover:bg-[#f4f4f5] hover:text-ink' => $testsSubTab !== $testsView,
                            ])>
                            <span class="text-[13px] font-semibold">{{ $tabMeta['label'] }}</span>
                            <span class="mt-0.5 w-full truncate text-[11.5px] {{ $testsSubTab === $testsView ? 'text-white/65' : 'text-ink-faint' }}">{{ $tabMeta['hint'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ───────────── banks ───────────── --}}
            @if ($testsSubTab === 'banks')
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.25fr)_360px]">
                    <div class="{{ $section }}">
                        <div class="{{ $sectionHead }}">
                            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_bank_setup') }}</p>
                        </div>
                        <div class="grid gap-4 p-5">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1.3fr)_minmax(200px,0.7fr)]">
                                <div>
                                    <x-label for="test-bank-name">{{ __($d.'.fields.test_bank_name') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-name" wire:model="bankForm.name" />
                                    @error('bankForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-bank-code">{{ __($d.'.fields.test_bank_code') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-code" wire:model="bankForm.code" />
                                    @error('bankForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <x-label for="test-bank-pass-score">{{ __($d.'.fields.pass_score') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-pass-score" type="number" step="0.01" wire:model="bankForm.pass_score" />
                                    @error('bankForm.pass_score') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-bank-duration">{{ __($d.'.fields.duration_minutes') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-duration" type="number" wire:model="bankForm.duration_minutes" />
                                    @error('bankForm.duration_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-bank-max-attempts">{{ __($d.'.fields.max_attempts') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-max-attempts" type="number" wire:model="bankForm.max_attempts" />
                                    @error('bankForm.max_attempts') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <x-label for="test-bank-description">{{ __($d.'.fields.description') }}</x-label>
                                <textarea id="test-bank-description" wire:model="bankForm.description" rows="4" class="{{ $textarea }}"></textarea>
                                @error('bankForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="flex flex-col gap-3 border-t border-hairline-subtle pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <label class="inline-flex items-center gap-2.5 text-[13px] text-ink-soft">
                                    <input type="checkbox" wire:model="bankForm.is_active" class="{{ $checkbox }}">
                                    {{ __($d.'.fields.is_active') }}
                                </label>
                                <button type="button" wire:click="storeTestBank" class="{{ $primary }}">{{ __($d.'.actions.save_test_bank') }}</button>
                            </div>
                        </div>
                    </div>

                    <aside class="{{ $section }} self-start">
                        <div class="{{ $sectionHead }}">
                            <p class="hrm-eyebrow">{{ __($d.'.tests_subtabs.banks') }}</p>
                        </div>
                        <div class="grid gap-2 p-4">
                            <p class="text-[12.5px] leading-6 text-ink-muted">Bank testin qaydasını yığır: keçid balı, vaxt limiti və cəhd sayı burada müəyyən olunur.</p>
                            <div class="{{ $note }}">
                                <p class="text-[12.5px] font-semibold text-ink">{{ __($d.'.fields.pass_score') }}</p>
                                <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">Keçid həddi testin keçmiş sayılıb-sayılmayacağını, həm də analitik hesabatlarda risk səviyyəsini formalaşdırır.</p>
                            </div>
                            <div class="{{ $note }}">
                                <p class="text-[12.5px] font-semibold text-ink">{{ __($d.'.fields.duration_minutes') }}</p>
                                <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">Müddət test workspace-də geri sayım kimi görünür və vaxt bitəndə cəhd avtomatik yekunlaşır.</p>
                            </div>
                            <div class="{{ $note }}">
                                <p class="text-[12.5px] font-semibold text-ink">{{ __($d.'.fields.max_attempts') }}</p>
                                <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">Təkrar cəhd siyasətini burada sabitləyirsən; session yaradılarkən istəsən ayrıca override edə bilərsən.</p>
                            </div>
                        </div>
                    </aside>
                </div>
            @endif

            {{-- ───────────── questions ───────────── --}}
            @if ($testsSubTab === 'questions')
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.2fr)_340px]">
                    <div class="{{ $section }}">
                        <div class="{{ $sectionHead }}">
                            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_question_setup') }}</p>
                        </div>
                        <div class="grid gap-4 p-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-question-bank"
                                        wire:model.live="questionForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                                    @error('questionForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.competency')" placeholder="---" mode="gray" class="w-full" instance="perf-question-competency"
                                        wire:model.live="questionForm.training_competency_id" :model="$this->competencyOptions()" search-model="searchTestCompetency"></x-ui.select-dropdown>
                                    @error('questionForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.question_type')" placeholder="---" mode="gray" class="w-full" instance="perf-question-type"
                                        wire:model.live="questionForm.question_type"
                                        :model="collect(['multiple_choice','open_answer','case_study','behavioral'])->map(fn ($item) => ['id' => $item, 'label' => __($d.'.question_types.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                    @error('questionForm.question_type') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <x-label for="test-question-prompt">{{ __($d.'.fields.prompt') }}</x-label>
                                <textarea id="test-question-prompt" wire:model="questionForm.prompt" rows="3" class="{{ $textarea }}"></textarea>
                                @error('questionForm.prompt') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <x-label for="test-question-max-score">{{ __($d.'.fields.max_score') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-question-max-score" type="number" step="0.01" wire:model="questionForm.max_score" />
                                    @error('questionForm.max_score') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-question-sort-order">{{ __($d.'.fields.sort_order') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-question-sort-order" type="number" wire:model="questionForm.sort_order" />
                                    @error('questionForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <x-label for="test-question-options">{{ __($d.'.fields.options_text') }}</x-label>
                                <textarea id="test-question-options" wire:model="questionForm.options_text" rows="4" class="{{ $textarea }}" placeholder="{{ __($d.'.placeholders.options_text') }}"></textarea>
                                <p class="mt-1 text-[11.5px] leading-5 text-ink-faint">{{ __($d.'.hints.options_text') }}</p>
                                @error('questionForm.options_text') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="flex flex-col gap-3 border-t border-hairline-subtle pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <label class="inline-flex items-center gap-2.5 text-[13px] text-ink-soft">
                                    <input type="checkbox" wire:model="questionForm.is_active" class="{{ $checkbox }}">
                                    {{ __($d.'.fields.is_active') }}
                                </label>
                                <button type="button" wire:click="storeTestQuestion" class="{{ $primary }}">{{ __($d.'.actions.save_test_question') }}</button>
                            </div>
                        </div>
                    </div>

                    <aside class="{{ $section }} self-start">
                        <div class="{{ $sectionHead }}">
                            <p class="hrm-eyebrow">{{ __($d.'.tests_subtabs.questions') }}</p>
                        </div>
                        <div class="grid gap-2 p-4">
                            @foreach ([
                                __($d.'.question_types.multiple_choice') => 'Variantlardan biri və ya bir neçəsi düzgün cavabdır; score option üzrə verilir.',
                                __($d.'.question_types.open_answer') => 'Sərbəst cavab toplanır, yekun bal yoxlayan tərəfindən sonradan yazılır.',
                                __($d.'.question_types.case_study') => 'Ssenariyə cavab kimi işləyir; əsasən açıq cavab kimi review mərhələsində qiymətləndirilir.',
                                __($d.'.question_types.behavioral') => 'Davranış və yanaşma tipli cavablar üçündür; manual review ilə yaxşı işləyir.',
                            ] as $title => $copy)
                                <div class="{{ $note }}">
                                    <p class="text-[12.5px] font-semibold text-ink">{{ $title }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ $copy }}</p>
                                </div>
                            @endforeach
                        </div>
                    </aside>
                </div>
            @endif

            {{-- ───────────── import ───────────── --}}
            @if ($testsSubTab === 'import')
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="{{ $section }} self-start">
                        <div class="{{ $sectionHead }}">
                            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.labels.import_workflow_title') }}</p>
                        </div>
                        <div class="grid gap-4 p-5">
                            <p class="text-[12.5px] leading-6 text-ink-muted">{{ __($d.'.labels.import_workflow_hint') }}</p>
                            <ol class="grid gap-2">
                                @foreach ([
                                    __($d.'.labels.import_step_download'),
                                    __($d.'.labels.import_step_fill'),
                                    __($d.'.labels.import_step_upload'),
                                    __($d.'.labels.import_step_review'),
                                ] as $index => $step)
                                    <li class="flex items-start gap-3 rounded-xl bg-[#fafafa] px-3.5 py-3">
                                        <span class="hrm-num flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink text-[11.5px] font-semibold text-white">{{ $index + 1 }}</span>
                                        <p class="text-[13px] leading-6 text-ink-soft">{{ $step }}</p>
                                    </li>
                                @endforeach
                            </ol>
                            <div class="rounded-xl border border-dashed border-hairline px-4 py-3.5">
                                <p class="hrm-eyebrow">{{ __($d.'.labels.import_template_preview_title') }}</p>
                                <p class="mt-1 text-[12px] leading-5 text-ink-muted">{{ __($d.'.labels.import_template_preview_hint') }}</p>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach (['bank_code', 'bank_name', 'competency_name', 'question_type', 'prompt', 'max_score', 'option_1_label', 'option_1_correct', 'option_1_score'] as $column)
                                        <span class="hrm-num rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11.5px] text-ink-muted">{{ $column }}</span>
                                    @endforeach
                                </div>
                                <div class="mt-4">
                                    <button type="button" wire:click="downloadTestQuestionImportTemplate" class="{{ $secondary }}">
                                        <x-icons.excel-icon size="h-4 w-4" />
                                        <span class="ml-1.5">{{ __($d.'.actions.download_import_template') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="{{ $section }} self-start">
                        <div class="{{ $sectionHead }}">
                            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.labels.import_upload_title') }}</p>
                        </div>
                        <div class="grid gap-4 p-5">
                            <p class="text-[12.5px] leading-6 text-ink-muted">{{ __($d.'.hints.test_question_import') }}</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <p class="rounded-xl bg-emerald-50 px-3.5 py-2.5 text-[12px] leading-5 text-emerald-800">{{ __($d.'.labels.import_supports_auto_create') }}</p>
                                <p class="rounded-xl bg-sky-50 px-3.5 py-2.5 text-[12px] leading-5 text-sky-800">{{ __($d.'.labels.import_supports_update') }}</p>
                            </div>
                            <div>
                                <x-ui.select-dropdown :label="__($d.'.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-question-import-bank"
                                    wire:model.live="testQuestionImportForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                                <p class="mt-1 text-[11.5px] text-ink-faint">{{ __($d.'.labels.import_target_bank_hint') }}</p>
                                @error('testQuestionImportForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="test-question-import-file">{{ __($d.'.fields.import_file') }}</x-label>
                                <input id="test-question-import-file" type="file" wire:model="testQuestionImportFile" accept=".xlsx,.xls,.csv"
                                    class="mt-1 block w-full rounded-xl border border-dashed border-hairline bg-[#fafafa] px-3 py-3 text-[13px] text-ink-muted file:mr-3 file:rounded-lg file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-[12.5px] file:font-semibold file:text-white">
                                @error('testQuestionImportFile') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="flex justify-end border-t border-hairline-subtle pt-4">
                                <button type="button" wire:click="importTestQuestions" wire:loading.attr="disabled" wire:target="importTestQuestions,testQuestionImportFile" class="{{ $primary }}">{{ __($d.'.actions.import_test_questions') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ───────────── sessions ───────────── --}}
            @if ($testsSubTab === 'sessions')
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.25fr)_340px]">
                    <div class="{{ $section }}">
                        <div class="{{ $sectionHead }}">
                            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_session_setup') }}</p>
                        </div>
                        <div class="grid gap-4 p-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.cycle')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-cycle"
                                        wire:model.live="sessionForm.performance_cycle_id" :model="$this->cycleOptions()" search-model="searchCycle"></x-ui.select-dropdown>
                                    @error('sessionForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-bank"
                                        wire:model.live="sessionForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                                    @error('sessionForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.personnel')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-personnel"
                                        wire:model.live="sessionForm.personnel_id" :model="$this->personnelOptions('searchTestPersonnel', 'personnel_id')" search-model="searchTestPersonnel"></x-ui.select-dropdown>
                                    @error('sessionForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.reviewer')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-reviewer"
                                        wire:model.live="sessionForm.reviewer_id" :model="$this->evaluatorOptions('searchTestReviewer', 'reviewer_id')" search-model="searchTestReviewer"></x-ui.select-dropdown>
                                    @error('sessionForm.reviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-session-scheduled-at">{{ __($d.'.fields.scheduled_at') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-scheduled-at" type="date" wire:model="sessionForm.scheduled_at" />
                                    @error('sessionForm.scheduled_at') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-session-available-until">{{ __($d.'.fields.available_until') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-available-until" type="date" wire:model="sessionForm.available_until" />
                                    @error('sessionForm.available_until') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                                <div>
                                    <x-label for="test-session-pass-score">{{ __($d.'.fields.pass_score') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-pass-score" type="number" step="0.01" wire:model="sessionForm.pass_score" />
                                </div>
                                <div>
                                    <x-label for="test-session-duration">{{ __($d.'.fields.duration_minutes') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-duration" type="number" wire:model="sessionForm.duration_minutes" />
                                </div>
                                <div>
                                    <x-label for="test-session-max-attempts">{{ __($d.'.fields.max_attempts') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-max-attempts" type="number" wire:model="sessionForm.max_attempts" />
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.status')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-status" wire:model.live="sessionForm.status"
                                        :model="collect(['assigned','in_progress','completed','closed'])->map(fn ($item) => ['id' => $item, 'label' => __($d.'.test_statuses.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                    @error('sessionForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="flex justify-end border-t border-hairline-subtle pt-4">
                                <button type="button" wire:click="storeTestSession" class="{{ $primary }}">{{ __($d.'.actions.save_test_session') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 self-start">
                        <div class="{{ $section }}">
                            <div class="grid gap-3 p-4">
                                <div>
                                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_taking_workspace') }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ __($d.'.labels.test_taking_workspace_hint') }}</p>
                                </div>
                                <div class="grid gap-2">
                                    <a href="{{ $workspaceLinks['personnel_links'] }}" class="{{ $secondary }} text-center">{{ __($d.'.actions.open_user_personnel_links') }}</a>
                                    <a href="{{ $workspaceLinks['test_workspace'] }}" target="_blank" class="{{ $primary }} text-center">{{ __($d.'.actions.open_test_workspace') }}</a>
                                </div>
                            </div>
                        </div>

                        <aside class="{{ $section }}">
                            <div class="{{ $sectionHead }}">
                                <p class="hrm-eyebrow">{{ __($d.'.tests_subtabs.sessions') }}</p>
                            </div>
                            <div class="grid gap-2 p-4">
                                <div class="{{ $note }}">
                                    <p class="text-[12.5px] font-semibold text-ink">{{ __($d.'.fields.available_until') }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">Son tarix bitəndən sonra test workspace yeni cəhd başlatmağa imkan verməz.</p>
                                </div>
                                <div class="{{ $note }}">
                                    <p class="text-[12.5px] font-semibold text-ink">{{ __($d.'.fields.reviewer') }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">Açıq cavab, case və davranış tipli suallar bu yoxlayan tərəfindən sonradan yoxlanır.</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            @endif

            {{-- ───────────── review ───────────── --}}
            @if ($testsSubTab === 'review')
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[300px_minmax(0,1fr)]">
                    <div class="flex flex-col gap-4 self-start">
                        <div class="{{ $section }}">
                            <div class="grid gap-3 p-4">
                                <div>
                                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_taking_workspace') }}</p>
                                    <p class="mt-0.5 text-[12px] leading-5 text-ink-muted">{{ __($d.'.labels.test_taking_workspace_hint') }}</p>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                                    <a href="{{ $workspaceLinks['personnel_links'] }}" class="{{ $secondary }} text-center">{{ __($d.'.actions.open_user_personnel_links') }}</a>
                                    <a href="{{ $workspaceLinks['test_workspace'] }}" target="_blank" class="{{ $primary }} text-center">{{ __($d.'.actions.open_test_workspace') }}</a>
                                </div>
                            </div>
                        </div>

                        <div class="{{ $section }}">
                            <div class="{{ $sectionHead }}">
                                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.attempt_finalize') }}</p>
                            </div>
                            <div class="grid gap-3 p-4">
                                <div>
                                    <x-ui.select-dropdown :label="__($d.'.fields.attempt')" placeholder="---" mode="gray" class="w-full" instance="perf-finalize-attempt"
                                        direction="auto"
                                        wire:model.live="attemptSubmitForm.performance_test_attempt_id" :model="$this->attemptOptions()" search-model="searchTestAttempt"></x-ui.select-dropdown>
                                    @error('attemptSubmitForm.performance_test_attempt_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <button type="button" wire:click="finalizeAttempt" class="{{ $primary }}">{{ __($d.'.actions.submit_attempt') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-col gap-4">
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                            <div class="{{ $section }} self-start">
                                <div class="{{ $sectionHead }}">
                                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.attempt_capture') }}</p>
                                </div>
                                <div class="grid gap-4 p-5">
                                    <div>
                                        <x-ui.select-dropdown :label="__($d.'.fields.test_session')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-session"
                                            direction="auto"
                                            wire:model.live="attemptAnswerForm.performance_test_session_id" :model="$this->testSessionOptions()" search-model="searchTestSession"></x-ui.select-dropdown>
                                        @error('attemptAnswerForm.performance_test_session_id') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_140px]">
                                        <div>
                                            <x-ui.select-dropdown :label="__($d.'.fields.question')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-question"
                                                direction="auto"
                                                wire:model.live="attemptAnswerForm.performance_test_question_id" :model="$this->testQuestionOptions()" search-model="searchTestQuestion"></x-ui.select-dropdown>
                                            @error('attemptAnswerForm.performance_test_question_id') <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                        <div>
                                            <x-label for="attempt-no">{{ __($d.'.fields.attempt_no') }}</x-label>
                                            <x-livewire-input mode="gray" id="attempt-no" type="number" wire:model="attemptAnswerForm.attempt_no" />
                                            @error('attemptAnswerForm.attempt_no') <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                    </div>
                                    @if (data_get($attemptAnswerForm, 'performance_test_question_id') && optional(\App\Models\PerformanceTestQuestion::find(data_get($attemptAnswerForm, 'performance_test_question_id')))->isAutoScored())
                                        <div>
                                            <x-ui.select-dropdown :label="__($d.'.fields.option')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-option"
                                                direction="auto"
                                                wire:model.live="attemptAnswerForm.selected_option_id" :model="$this->testQuestionOptionChoices()"></x-ui.select-dropdown>
                                            @error('attemptAnswerForm.selected_option_id') <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                    @endif
                                    <div>
                                        <x-label for="attempt-answer-text">{{ __($d.'.fields.answer_text') }}</x-label>
                                        <textarea id="attempt-answer-text" wire:model="attemptAnswerForm.answer_text" rows="3" class="{{ $textarea }}"></textarea>
                                        @error('attemptAnswerForm.answer_text') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div class="flex justify-end border-t border-hairline-subtle pt-4">
                                        <button type="button" wire:click="storeAttemptAnswer" class="{{ $primary }}">{{ __($d.'.actions.save_attempt_answer') }}</button>
                                    </div>
                                </div>
                            </div>

                            <div class="{{ $section }} self-start">
                                <div class="{{ $sectionHead }}">
                                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.open_answer_review') }}</p>
                                </div>
                                <div class="grid gap-4 p-5">
                                    <div>
                                        <x-ui.select-dropdown :label="__($d.'.fields.answer')" placeholder="---" mode="gray" class="w-full" instance="perf-review-answer"
                                            direction="auto"
                                            wire:model.live="reviewForm.performance_test_attempt_answer_id" :model="$this->reviewAnswerOptions()" search-model="searchReviewAnswer"></x-ui.select-dropdown>
                                        @error('reviewForm.performance_test_attempt_answer_id') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div class="sm:max-w-[220px]">
                                        <x-label for="review-score">{{ __($d.'.fields.review_score') }}</x-label>
                                        <x-livewire-input mode="gray" id="review-score" type="number" step="0.01" wire:model="reviewForm.score" />
                                        @error('reviewForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div>
                                        <x-label for="review-feedback">{{ __($d.'.fields.feedback') }}</x-label>
                                        <x-ui.textarea id="review-feedback" wire:model="reviewForm.feedback" :rows="4" />
                                        @error('reviewForm.feedback') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div class="flex justify-end border-t border-hairline-subtle pt-4">
                                        <button type="button" wire:click="reviewAttemptAnswer" class="{{ $primary }}">{{ __($d.'.actions.review_answer') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <livewire:performance-evaluation.tests-summary :key="'performance-evaluation-tests-summary-'.$testsSummaryVersion" lazy />
                    </div>
                </div>
            @endif
        </div>
    @endif
