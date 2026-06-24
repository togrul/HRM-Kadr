    @if ($activeTab === 'tests')
        @php
            $testsTabs = [
                'banks' => [
                    'label' => __('performance_evaluation::dashboard.tests_subtabs.banks'),
                    'hint' => __('performance_evaluation::dashboard.cards.test_bank_setup'),
                ],
                'questions' => [
                    'label' => __('performance_evaluation::dashboard.tests_subtabs.questions'),
                    'hint' => __('performance_evaluation::dashboard.cards.test_question_setup'),
                ],
                'import' => [
                    'label' => __('performance_evaluation::dashboard.tests_subtabs.import'),
                    'hint' => __('performance_evaluation::dashboard.cards.test_question_import'),
                ],
                'sessions' => [
                    'label' => __('performance_evaluation::dashboard.tests_subtabs.sessions'),
                    'hint' => __('performance_evaluation::dashboard.cards.test_session_setup'),
                ],
                'review' => [
                    'label' => __('performance_evaluation::dashboard.tests_subtabs.review'),
                    'hint' => __('performance_evaluation::dashboard.cards.open_answer_review'),
                ],
            ];
        @endphp
        <div class="space-y-4">
            <div class="rounded-[30px] border border-zinc-200 bg-white/95 p-2 shadow-sm">
                <div class="grid gap-2 lg:grid-cols-5">
                    @foreach ($testsTabs as $testsView => $tabMeta)
                        <button type="button"
                            wire:click.prevent="switchTestsSubTab('{{ $testsView }}')"
                            class="{{ $testsSubTab === $testsView ? 'bg-zinc-900 text-white shadow-lg shadow-zinc-900/10' : 'bg-zinc-50 text-zinc-600 hover:bg-zinc-100' }} flex min-w-0 flex-col items-start rounded-[24px] px-4 py-3 text-left transition">
                            <span class="text-base font-semibold">{{ $tabMeta['label'] }}</span>
                            <span class="mt-1 text-xs leading-5 {{ $testsSubTab === $testsView ? 'text-white/70' : 'text-zinc-400' }}">{{ $tabMeta['hint'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($testsSubTab === 'banks')
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_360px]">
                    <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_bank_setup')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                        <div class="grid gap-4">
                            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(240px,0.7fr)]">
                                <div>
                                    <x-label for="test-bank-name">{{ __('performance_evaluation::dashboard.fields.test_bank_name') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-name" wire:model.defer="bankForm.name" />
                                    @error('bankForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-bank-code">{{ __('performance_evaluation::dashboard.fields.test_bank_code') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-code" wire:model.defer="bankForm.code" />
                                    @error('bankForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <x-label for="test-bank-pass-score">{{ __('performance_evaluation::dashboard.fields.pass_score') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-pass-score" type="number" step="0.01" wire:model.defer="bankForm.pass_score" />
                                    @error('bankForm.pass_score') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-bank-duration">{{ __('performance_evaluation::dashboard.fields.duration_minutes') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-duration" type="number" wire:model.defer="bankForm.duration_minutes" />
                                    @error('bankForm.duration_minutes') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-bank-max-attempts">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-bank-max-attempts" type="number" wire:model.defer="bankForm.max_attempts" />
                                    @error('bankForm.max_attempts') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <x-label for="test-bank-description">{{ __('performance_evaluation::dashboard.fields.description') }}</x-label>
                                <textarea id="test-bank-description" wire:model.defer="bankForm.description" class="min-h-28 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                                @error('bankForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="flex flex-col gap-3 rounded-[24px] border border-zinc-200 bg-zinc-50 px-4 py-4 md:flex-row md:items-center md:justify-between">
                                <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                    <input type="checkbox" wire:model.defer="bankForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                    {{ __('performance_evaluation::dashboard.fields.is_active') }}
                                </label>
                                <x-button mode="black" wire:click="storeTestBank">{{ __('performance_evaluation::dashboard.actions.save_test_bank') }}</x-button>
                            </div>
                        </div>
                    </x-surface-card>

                    <div class="space-y-4">
                        <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('performance_evaluation::dashboard.tests_subtabs.banks') }}</p>
                            <h3 class="mt-3 text-lg font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.test_bank_setup') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Bank testin qaydasını yığır: keçid balı, vaxt limiti və cəhd sayı burada müəyyən olunur.</p>
                            <div class="mt-4 grid gap-3">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('performance_evaluation::dashboard.fields.pass_score') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600">Keçid həddi testin keçmiş sayılıb-sayılmayacağını, həm də analitik hesabatlarda risk səviyyəsini formalaşdırır.</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('performance_evaluation::dashboard.fields.duration_minutes') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600">Müddət test workspace-də geri sayım kimi görünür və vaxt bitəndə cəhd avtomatik yekunlaşır.</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-zinc-600">Təkrar cəhd siyasətini burada sabitləyirsən; session yaradılarkən istəsən ayrıca override edə bilərsən.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($testsSubTab === 'questions')
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_340px]">
                    <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_question_setup')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                        <div class="grid gap-4">
                            <div class="grid gap-4 xl:grid-cols-3">
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-question-bank"
                                        wire:model.live="questionForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                                    @error('questionForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.competency')" placeholder="---" mode="gray" class="w-full" instance="perf-question-competency"
                                        wire:model.live="questionForm.training_competency_id" :model="$this->competencyOptions()" search-model="searchTestCompetency"></x-ui.select-dropdown>
                                    @error('questionForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.question_type')" placeholder="---" mode="gray" class="w-full" instance="perf-question-type"
                                        wire:model.live="questionForm.question_type"
                                        :model="collect(['multiple_choice','open_answer','case_study','behavioral'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.question_types.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                    @error('questionForm.question_type') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <x-label for="test-question-prompt">{{ __('performance_evaluation::dashboard.fields.prompt') }}</x-label>
                                <textarea id="test-question-prompt" wire:model.defer="questionForm.prompt" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                                @error('questionForm.prompt') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <x-label for="test-question-max-score">{{ __('performance_evaluation::dashboard.fields.max_score') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-question-max-score" type="number" step="0.01" wire:model.defer="questionForm.max_score" />
                                    @error('questionForm.max_score') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-question-sort-order">{{ __('performance_evaluation::dashboard.fields.sort_order') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-question-sort-order" type="number" wire:model.defer="questionForm.sort_order" />
                                    @error('questionForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <x-label for="test-question-options">{{ __('performance_evaluation::dashboard.fields.options_text') }}</x-label>
                                <textarea id="test-question-options" wire:model.defer="questionForm.options_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500" placeholder="{{ __('performance_evaluation::dashboard.placeholders.options_text') }}"></textarea>
                                <p class="mt-1 text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.hints.options_text') }}</p>
                                @error('questionForm.options_text') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="flex flex-col gap-3 rounded-[24px] border border-zinc-200 bg-zinc-50 px-4 py-4 md:flex-row md:items-center md:justify-between">
                                <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                    <input type="checkbox" wire:model.defer="questionForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                    {{ __('performance_evaluation::dashboard.fields.is_active') }}
                                </label>
                                <x-button mode="black" wire:click="storeTestQuestion">{{ __('performance_evaluation::dashboard.actions.save_test_question') }}</x-button>
                            </div>
                        </div>
                    </x-surface-card>

                    <div class="space-y-4">
                        <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('performance_evaluation::dashboard.tests_subtabs.questions') }}</p>
                            <h3 class="mt-3 text-lg font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.test_question_setup') }}</h3>
                            <div class="mt-4 space-y-3">
                                @foreach ([
                                    __('performance_evaluation::dashboard.question_types.multiple_choice') => 'Variantlardan biri və ya bir neçəsi düzgün cavabdır; score option üzrə verilir.',
                                    __('performance_evaluation::dashboard.question_types.open_answer') => 'Sərbəst cavab toplanır, yekun bal yoxlayan tərəfindən sonradan yazılır.',
                                    __('performance_evaluation::dashboard.question_types.case_study') => 'Ssenariyə cavab kimi işləyir; əsasən açıq cavab kimi review mərhələsində qiymətləndirilir.',
                                    __('performance_evaluation::dashboard.question_types.behavioral') => 'Davranış və yanaşma tipli cavablar üçündür; manual review ilə yaxşı işləyir.',
                                ] as $title => $copy)
                                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                        <p class="text-sm font-semibold text-zinc-900">{{ $title }}</p>
                                        <p class="mt-1 text-xs leading-6 text-zinc-500">{{ $copy }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($testsSubTab === 'import')
                <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_question_import')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-4 rounded-[28px] border border-zinc-200 bg-gradient-to-b from-zinc-50 to-white p-5">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.import_workflow_title') }}</p>
                                <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.import_workflow_hint') }}</p>
                            </div>
                            <div class="space-y-3">
                                @foreach ([
                                    __('performance_evaluation::dashboard.labels.import_step_download'),
                                    __('performance_evaluation::dashboard.labels.import_step_fill'),
                                    __('performance_evaluation::dashboard.labels.import_step_upload'),
                                    __('performance_evaluation::dashboard.labels.import_step_review'),
                                ] as $index => $step)
                                    <div class="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white">{{ $index + 1 }}</span>
                                        <p class="text-sm leading-6 text-zinc-700">{{ $step }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('performance_evaluation::dashboard.labels.import_template_preview_title') }}</p>
                                <p class="mt-2 text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.import_template_preview_hint') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ([
                                        'bank_code',
                                        'bank_name',
                                        'competency_name',
                                        'question_type',
                                        'prompt',
                                        'max_score',
                                        'option_1_label',
                                        'option_1_correct',
                                        'option_1_score',
                                    ] as $column)
                                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-medium text-zinc-700">{{ $column }}</span>
                                    @endforeach
                                </div>
                                <div class="mt-4">
                                    <x-button mode="secondary" wire:click="downloadTestQuestionImportTemplate">{{ __('performance_evaluation::dashboard.actions.download_import_template') }}</x-button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                            <div class="grid gap-4 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                                <div class="space-y-2">
                                    <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.import_upload_title') }}</p>
                                    <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.hints.test_question_import') }}</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-6 text-emerald-700">
                                        {{ __('performance_evaluation::dashboard.labels.import_supports_auto_create') }}
                                    </div>
                                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-6 text-sky-700">
                                        {{ __('performance_evaluation::dashboard.labels.import_supports_update') }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-[minmax(0,0.75fr)_minmax(0,1.25fr)]">
                                <div class="space-y-2">
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-question-import-bank"
                                        wire:model.live="testQuestionImportForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                                    <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.labels.import_target_bank_hint') }}</p>
                                    @error('testQuestionImportForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>

                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                                    <x-label for="test-question-import-file">{{ __('performance_evaluation::dashboard.fields.import_file') }}</x-label>
                                    <input id="test-question-import-file" type="file" wire:model="testQuestionImportFile" class="mt-2 block w-full rounded-2xl bg-white px-3 py-3 text-sm shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-zinc-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white" accept=".xlsx,.xls,.csv">
                                    @error('testQuestionImportFile') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-button mode="black" wire:click="importTestQuestions">{{ __('performance_evaluation::dashboard.actions.import_test_questions') }}</x-button>
                            </div>
                        </div>
                    </div>
                </x-surface-card>
            @endif

            @if ($testsSubTab === 'sessions')
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.25fr)_340px]">
                    <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_session_setup')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                        <div class="space-y-4">
                            <div class="grid gap-4 xl:grid-cols-3">
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.cycle')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-cycle"
                                        wire:model.live="sessionForm.performance_cycle_id" :model="$this->cycleOptions()" search-model="searchCycle"></x-ui.select-dropdown>
                                    @error('sessionForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_bank')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-bank"
                                        wire:model.live="sessionForm.performance_test_bank_id" :model="$this->testBankOptions()" search-model="searchTestBank"></x-ui.select-dropdown>
                                    @error('sessionForm.performance_test_bank_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.personnel')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-personnel"
                                        wire:model.live="sessionForm.personnel_id" :model="$this->personnelOptions('searchTestPersonnel', 'personnel_id')" search-model="searchTestPersonnel"></x-ui.select-dropdown>
                                    @error('sessionForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-3">
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.reviewer')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-reviewer"
                                        wire:model.live="sessionForm.reviewer_id" :model="$this->evaluatorOptions('searchTestReviewer', 'reviewer_id')" search-model="searchTestReviewer"></x-ui.select-dropdown>
                                    @error('sessionForm.reviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-session-scheduled-at">{{ __('performance_evaluation::dashboard.fields.scheduled_at') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-scheduled-at" type="date" wire:model.defer="sessionForm.scheduled_at" />
                                    @error('sessionForm.scheduled_at') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <x-label for="test-session-available-until">{{ __('performance_evaluation::dashboard.fields.available_until') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-available-until" type="date" wire:model.defer="sessionForm.available_until" />
                                    @error('sessionForm.available_until') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 xl:grid-cols-4">
                                <div>
                                    <x-label for="test-session-pass-score">{{ __('performance_evaluation::dashboard.fields.pass_score') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-pass-score" type="number" step="0.01" wire:model.defer="sessionForm.pass_score" />
                                </div>
                                <div>
                                    <x-label for="test-session-duration">{{ __('performance_evaluation::dashboard.fields.duration_minutes') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-duration" type="number" wire:model.defer="sessionForm.duration_minutes" />
                                </div>
                                <div>
                                    <x-label for="test-session-max-attempts">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}</x-label>
                                    <x-livewire-input mode="gray" id="test-session-max-attempts" type="number" wire:model.defer="sessionForm.max_attempts" />
                                </div>
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.status')" placeholder="---" mode="gray" class="w-full" instance="perf-test-session-status" wire:model.live="sessionForm.status"
                                        :model="collect(['assigned','in_progress','completed','closed'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.test_statuses.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                    @error('sessionForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <x-button mode="black" wire:click="storeTestSession">{{ __('performance_evaluation::dashboard.actions.save_test_session') }}</x-button>
                            </div>
                        </div>
                    </x-surface-card>

                    <div class="space-y-4">
                        <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.test_taking_workspace') }}</p>
                                <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.test_taking_workspace_hint') }}</p>
                            </div>
                            <div class="mt-4 grid gap-2">
                                <a href="{{ route('performance-evaluation.user-personnel-links', ['return' => url()->current()]) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-center text-sm font-medium leading-tight text-zinc-700 shadow-sm">
                                    {{ __('performance_evaluation::dashboard.actions.open_user_personnel_links') }}
                                </a>
                                <a href="{{ route('performance-evaluation.test-workspace', ['return' => url()->current()]) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-zinc-900 px-4 py-3 text-center text-sm font-medium leading-tight text-white">
                                    {{ __('performance_evaluation::dashboard.actions.open_test_workspace') }}
                                </a>
                            </div>
                        </div>

                        <div class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-400">{{ __('performance_evaluation::dashboard.tests_subtabs.sessions') }}</p>
                            <div class="mt-4 grid gap-3">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.fields.available_until') }}</p>
                                    <p class="mt-1 text-xs leading-6 text-zinc-500">Son tarix bitəndən sonra test workspace yeni cəhd başlatmağa imkan verməz.</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.fields.reviewer') }}</p>
                                    <p class="mt-1 text-xs leading-6 text-zinc-500">Açıq cavab, case və davranış tipli suallar bu yoxlayan tərəfindən sonradan yoxlanır.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($testsSubTab === 'review')
                <div class="grid gap-4 xl:grid-cols-[300px_minmax(0,1fr)]">
                    <div class="space-y-4">
                        <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 px-4 py-4">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.test_taking_workspace') }}</p>
                                <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.test_taking_workspace_hint') }}</p>
                            </div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                                <a href="{{ route('performance-evaluation.user-personnel-links', ['return' => url()->current()]) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-center text-sm font-medium leading-tight text-zinc-700 shadow-sm">
                                    {{ __('performance_evaluation::dashboard.actions.open_user_personnel_links') }}
                                </a>
                                <a href="{{ route('performance-evaluation.test-workspace', ['return' => url()->current()]) }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-zinc-900 px-4 py-3 text-center text-sm font-medium leading-tight text-white">
                                    {{ __('performance_evaluation::dashboard.actions.open_test_workspace') }}
                                </a>
                            </div>
                        </div>

                        <x-surface-card :title="__('performance_evaluation::dashboard.cards.attempt_finalize')" icon="icons.clock-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                            <div class="grid gap-3">
                                <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.attempt')" placeholder="---" mode="gray" class="w-full" instance="perf-finalize-attempt"
                                        direction="auto"
                                        wire:model.live="attemptSubmitForm.performance_test_attempt_id" :model="$this->attemptOptions()" search-model="searchTestAttempt"></x-ui.select-dropdown>
                                    @error('attemptSubmitForm.performance_test_attempt_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <x-button mode="black" wire:click="finalizeAttempt">{{ __('performance_evaluation::dashboard.actions.submit_attempt') }}</x-button>
                            </div>
                        </x-surface-card>

                    </div>

                    <div class="space-y-4">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                            <x-surface-card :title="__('performance_evaluation::dashboard.cards.attempt_capture')" icon="icons.pending-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                                <div class="grid gap-3">
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.test_session')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-session"
                                        direction="auto"
                                        wire:model.live="attemptAnswerForm.performance_test_session_id" :model="$this->testSessionOptions()" search-model="searchTestSession"></x-ui.select-dropdown>
                                    @error('attemptAnswerForm.performance_test_session_id') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_160px]">
                                    <div>
                                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.question')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-question"
                                        direction="auto"
                                        wire:model.live="attemptAnswerForm.performance_test_question_id" :model="$this->testQuestionOptions()" search-model="searchTestQuestion"></x-ui.select-dropdown>
                                    @error('attemptAnswerForm.performance_test_question_id') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div>
                                        <x-label for="attempt-no">{{ __('performance_evaluation::dashboard.fields.attempt_no') }}</x-label>
                                        <x-livewire-input mode="gray" id="attempt-no" type="number" wire:model.defer="attemptAnswerForm.attempt_no" />
                                        @error('attemptAnswerForm.attempt_no') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                </div>
                                @if (data_get($attemptAnswerForm, 'performance_test_question_id') && optional(\App\Models\PerformanceTestQuestion::find(data_get($attemptAnswerForm, 'performance_test_question_id')))->isAutoScored())
                                    <div>
                                        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.option')" placeholder="---" mode="gray" class="w-full" instance="perf-attempt-option"
                                            direction="auto"
                                            wire:model.live="attemptAnswerForm.selected_option_id" :model="$this->testQuestionOptionChoices()"></x-ui.select-dropdown>
                                        @error('attemptAnswerForm.selected_option_id') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                @endif
                                <div>
                                    <x-label for="attempt-answer-text">{{ __('performance_evaluation::dashboard.fields.answer_text') }}</x-label>
                                    <textarea id="attempt-answer-text" wire:model.defer="attemptAnswerForm.answer_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                                    @error('attemptAnswerForm.answer_text') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div class="flex flex-wrap justify-end gap-3">
                                    <x-button mode="black" wire:click="storeAttemptAnswer">{{ __('performance_evaluation::dashboard.actions.save_attempt_answer') }}</x-button>
                                </div>
                            </x-surface-card>

                            <x-surface-card :title="__('performance_evaluation::dashboard.cards.open_answer_review')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                                <div class="grid gap-3">
                                    <div>
                                        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.answer')" placeholder="---" mode="gray" class="w-full" instance="perf-review-answer"
                                            direction="auto"
                                            wire:model.live="reviewForm.performance_test_attempt_answer_id" :model="$this->reviewAnswerOptions()" search-model="searchReviewAnswer"></x-ui.select-dropdown>
                                        @error('reviewForm.performance_test_attempt_answer_id') <x-validation>{{ $message }}</x-validation> @enderror
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-[180px_minmax(0,1fr)]">
                                        <div>
                                            <x-label for="review-score">{{ __('performance_evaluation::dashboard.fields.review_score') }}</x-label>
                                            <x-livewire-input mode="gray" id="review-score" type="number" step="0.01" wire:model.defer="reviewForm.score" />
                                            @error('reviewForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                        <div>
                                            <x-label for="review-feedback">{{ __('performance_evaluation::dashboard.fields.feedback') }}</x-label>
                                            <textarea id="review-feedback" wire:model.defer="reviewForm.feedback" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                                            @error('reviewForm.feedback') <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <x-button mode="black" wire:click="reviewAttemptAnswer">{{ __('performance_evaluation::dashboard.actions.review_answer') }}</x-button>
                                    </div>
                                </div>
                            </x-surface-card>
                        </div>

                        <livewire:performance-evaluation.tests-summary :key="'performance-evaluation-tests-summary-'.$testsSummaryVersion" lazy />
                    </div>
                </div>
            @endif
        </div>
    @endif
