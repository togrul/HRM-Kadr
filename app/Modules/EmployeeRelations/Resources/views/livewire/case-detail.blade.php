@php
    $labelClass = 'hrm-eyebrow block pb-1';
    $abilities = $this->abilities;
    $status = $case->status;
    $editableFacts = $abilities['manage'] && in_array($status->value, ['open', 'investigating'], true);
    $targets = $this->transitionTargets();
    $deadline = $this->deadline;
    $messageTone = fn (string $level): string => match ($level) {
        'error' => 'border-[#fecdd3] bg-[#fff1f2] text-[#be123c]',
        'warning' => 'border-[#fde68a] bg-[#fffbeb] text-[#b45309]',
        default => 'border-hairline bg-[#fafafa] text-ink-soft',
    };
@endphp

<div class="flex flex-col">
    <x-page-header
        :title="$case->title"
        :breadcrumb="$case->label()"
        :breadcrumb-root="__('employee_relations::cases.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button :href="route('employee-relations')" wire:navigate>{{ __('employee_relations::cases.actions.back') }}</x-pill-button>
        </x-slot:actions>

        <div class="flex flex-wrap items-center gap-2">
            <x-small-badge mode="secondary" dot>{{ $status->label() }}</x-small-badge>
            <span class="text-[12px] text-ink-faint">{{ $case->type?->name }}</span>
            @if ($case->isOverdue())
                <x-small-badge mode="rose">{{ __('employee_relations::cases.labels.overdue_since', ['date' => $case->stage_due_at?->format('d.m.Y')]) }}</x-small-badge>
            @elseif ($status->hasSla() && $case->stage_due_at)
                <span class="hrm-num text-[12px] text-ink-faint">{{ __('employee_relations::cases.labels.due_at', ['date' => $case->stage_due_at->format('d.m.Y')]) }}</span>
            @endif
            @if ($case->order_log_id)
                <x-small-badge mode="violet">{{ __('employee_relations::cases.labels.order_linked', ['id' => $case->order_log_id]) }}</x-small-badge>
            @endif
        </div>
    </x-page-header>

    <div class="grid gap-4 px-4 py-4 sm:px-5 xl:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-4 xl:col-span-2">
            {{-- ===================== facts ===================== --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.facts') }}</h2>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.description') }}</p>
                        <p class="whitespace-pre-line text-[13px] leading-6 text-ink-soft">{{ $case->description }}</p>
                    </div>
                    <div>
                        <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.complainant_personnel_id') }}</p>
                        <p class="text-[13px] text-ink">{{ $complainantName ?? '—' }}</p>
                        @if ($revealedName)
                            <p class="mt-1 rounded-lg border border-[#fde68a] bg-[#fffbeb] px-2.5 py-1.5 text-[12.5px] font-medium text-[#b45309]">{{ __('employee_relations::cases.labels.revealed', ['name' => $revealedName]) }}</p>
                        @elseif ($abilities['reveal'])
                            <div class="mt-2 flex flex-col gap-2">
                                <x-ui.input wire:model="revealReason" type="text" :placeholder="__('employee_relations::cases.placeholders.reveal_reason')" />
                                @error('revealReason') <x-validation>{{ $message }}</x-validation> @enderror
                                <div>
                                    <x-pill-button variant="danger" wire:click="revealReporter" wire:loading.attr="disabled" wire:target="revealReporter">{{ __('employee_relations::cases.actions.reveal') }}</x-pill-button>
                                </div>
                                <p class="text-[11px] text-ink-faint">{{ __('employee_relations::cases.hints.reveal') }}</p>
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.channel') }}</p>
                        <p class="text-[13px] text-ink">{{ __('employee_relations::cases.channels.'.$case->channel) }}</p>
                        @if ($case->subject_description)
                            <p class="mt-2 {{ $labelClass }}">{{ __('employee_relations::cases.fields.subject_description') }}</p>
                            <p class="text-[13px] text-ink-soft">{{ $case->subject_description }}</p>
                        @endif
                    </div>

                    @if ($editableFacts)
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.type')" :error="$errors->first('facts.case_type_id')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model="facts.case_type_id">
                                @foreach ($typeOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.subject_personnel_id')" :error="$errors->first('facts.subject_personnel_id')" containerClass="!space-y-0" :labelClass="$labelClass">
                            @if (filled($facts['subject_personnel_id'] ?? null))
                                <div class="flex items-center justify-between gap-2 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2 text-[13px] text-ink">
                                    <span class="truncate">{{ $facts['subject_label'] ?? '' }}</span>
                                    <button type="button" class="text-[12px] text-ink-muted hover:text-ink" wire:click="$set('facts.subject_personnel_id', null)">{{ __('employee_relations::cases.actions.clear') }}</button>
                                </div>
                            @else
                                <x-ui.input icon="search" wire:model.live.debounce.300ms="facts.subject_search" :placeholder="__('employee_relations::cases.placeholders.personnel_search')" />
                                @php $subjectResults = $this->personnelResults((string) ($facts['subject_search'] ?? '')); @endphp
                                @if ($subjectResults !== [])
                                    <div class="mt-1 overflow-hidden rounded-lg border border-hairline bg-white">
                                        @foreach ($subjectResults as $result)
                                            <button type="button" wire:key="er-subject-{{ $result['id'] }}" wire:click="pickSubject({{ $result['id'] }})" class="flex w-full flex-col px-3 py-1.5 text-left hover:bg-[#fafafa]">
                                                <span class="text-[12.5px] font-medium text-ink">{{ $result['name'] }}</span>
                                                <span class="text-[11px] text-ink-faint">{{ $result['meta'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.occurred_at')" :error="$errors->first('facts.occurred_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model="facts.occurred_at" type="date" class="hrm-num" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.discovered_at')" :hint="__('employee_relations::cases.hints.discovered_at')" :error="$errors->first('facts.discovered_at')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model="facts.discovered_at" type="date" class="hrm-num" />
                        </x-ui.input-shell>
                        <div class="flex items-center justify-between gap-3 sm:col-span-2">
                            <x-checkbox name="facts.discovered_by_audit" model="facts.discovered_by_audit">{{ __('employee_relations::cases.fields.discovered_by_audit') }}</x-checkbox>
                            <x-pill-button wire:click="saveFacts" wire:loading.attr="disabled" wire:target="saveFacts">{{ __('employee_relations::cases.actions.save_facts') }}</x-pill-button>
                        </div>
                    @else
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.subject_personnel_id') }}</p>
                            <p class="text-[13px] text-ink">{{ $case->subject?->fullname ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.structure') }}</p>
                            <p class="text-[13px] text-ink">{{ $case->structure?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.occurred_at') }}</p>
                            <p class="hrm-num text-[13px] text-ink">{{ $case->occurred_at?->format('d.m.Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.discovered_at') }}</p>
                            <p class="hrm-num text-[13px] text-ink">{{ $case->discovered_at?->format('d.m.Y') ?? '—' }}</p>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ===================== stage transition ===================== --}}
            @if ($targets !== [])
                <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.transition') }}</h2>
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-3">
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.target_status')" :error="$errors->first('transitionTarget') ?: $errors->first('transition')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model="transitionTarget">
                                <option value="">—</option>
                                @foreach ($targets as $target)
                                    <option value="{{ $target->value }}">{{ $target->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.comment')" :error="$errors->first('comment')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                            <x-ui.input wire:model="transitionComment" type="text" />
                        </x-ui.input-shell>
                    </div>
                    <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                        <x-pill-button variant="primary" wire:click="changeStatus" wire:loading.attr="disabled" wire:target="changeStatus">{{ __('employee_relations::cases.actions.change_status') }}</x-pill-button>
                    </div>
                </section>
            @endif

            {{-- ===================== decision ===================== --}}
            @if ($case->decision_outcome)
                <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.decision') }}</h2>
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-3">
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.decision_outcome') }}</p>
                            <p class="text-[13px] font-medium text-ink">{{ $case->decision_outcome->label() }}</p>
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.decided_at') }}</p>
                            <p class="hrm-num text-[13px] text-ink">{{ $case->decided_at?->format('d.m.Y') }}</p>
                        </div>
                        <div>
                            <p class="{{ $labelClass }}">{{ __('employee_relations::cases.fields.appeal_deadline') }}</p>
                            <p class="hrm-num text-[13px] text-ink">{{ $case->appeal_deadline?->format('d.m.Y') ?? '—' }}</p>
                        </div>
                        <p class="whitespace-pre-line text-[13px] leading-6 text-ink-soft sm:col-span-3">{{ $case->decision_summary }}</p>
                    </div>
                </section>
            @endif

            @if ($abilities['manage'] && $status->value === 'investigating')
                <section class="overflow-hidden rounded-xl border border-hairline bg-white" wire:key="er-decision-form">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.make_decision') }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('employee_relations::cases.hints.decision') }}</p>
                    </div>
                    <div class="flex flex-col gap-2 px-4 pt-4">
                        @foreach (($deadline['messages'] ?? []) as $message)
                            <p wire:key="er-deadline-{{ $loop->index }}" class="rounded-lg border px-3 py-2 text-[12.5px] leading-5 {{ $messageTone($message['level']) }}">{{ $message['text'] }}</p>
                        @endforeach
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-3">
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.decision_outcome')" :error="$errors->first('decisionOutcome') ?: $errors->first('decision_outcome') ?: $errors->first('decision')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model="decisionOutcome">
                                <option value="">—</option>
                                @foreach ($this->decisionOptions() as $value => $option)
                                    <option value="{{ $value }}" @disabled($option['disabled'])>{{ $option['label'] }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.decision_summary')" :error="$errors->first('decisionSummary') ?: $errors->first('decision_summary')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                            <x-ui.textarea wire:model="decisionSummary" :rows="3" />
                        </x-ui.input-shell>
                    </div>
                    <div class="flex justify-end border-t border-hairline-subtle px-4 py-3">
                        <x-pill-button variant="primary" wire:click="decide" wire:loading.attr="disabled" wire:target="decide">{{ __('employee_relations::cases.actions.decide') }}</x-pill-button>
                    </div>
                </section>
            @endif

            {{-- ===================== appeals ===================== --}}
            @if ($this->appeals->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.appeals') }}</h2>
                    </div>
                    <div class="divide-y divide-hairline-subtle">
                        @foreach ($this->appeals as $appeal)
                            <div class="px-4 py-3" wire:key="er-appeal-{{ $appeal->id }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-small-badge mode="amber" dot>{{ __('employee_relations::cases.appeal_statuses.'.$appeal->status) }}</x-small-badge>
                                    <span class="text-[12px] text-ink-faint">{{ __('employee_relations::cases.appeal_roles.'.$appeal->appellant_role) }} · {{ $appeal->created_at?->format('d.m.Y H:i') }}</span>
                                </div>
                                <p class="mt-1.5 whitespace-pre-line text-[13px] leading-6 text-ink-soft">{{ $appeal->reason }}</p>
                                @if ($appeal->review_outcome)
                                    <p class="mt-1.5 text-[12.5px] text-ink">{{ __('employee_relations::cases.appeal_outcomes.'.$appeal->review_outcome) }} — {{ $appeal->review_comment }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if ($abilities['manage'] && $status->value === 'appealed')
                        <div class="flex flex-col gap-2 border-t border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center">
                            <x-ui.input wire:model="appealComment" type="text" :placeholder="__('employee_relations::cases.fields.comment')" />
                            <x-pill-button variant="primary" wire:click="startAppealReview" wire:loading.attr="disabled" wire:target="startAppealReview">{{ __('employee_relations::cases.actions.start_review') }}</x-pill-button>
                        </div>
                    @endif

                    @if ($abilities['manage'] && $status->value === 'appeal_review')
                        <div class="grid gap-3 border-t border-hairline-subtle p-4 sm:grid-cols-3">
                            @foreach (($deadline['messages'] ?? []) as $message)
                                <p wire:key="er-appeal-deadline-{{ $loop->index }}" class="rounded-lg border px-3 py-2 text-[12.5px] leading-5 sm:col-span-3 {{ $messageTone($message['level']) }}">{{ $message['text'] }}</p>
                            @endforeach
                            <x-ui.input-shell :label="__('employee_relations::cases.fields.appeal_outcome')" :error="$errors->first('appealOutcome')" containerClass="!space-y-0" :labelClass="$labelClass">
                                <x-ui.select wire:model.live="appealOutcome">
                                    <option value="">—</option>
                                    @foreach (['upheld', 'modified', 'annulled'] as $outcome)
                                        <option value="{{ $outcome }}">{{ __('employee_relations::cases.appeal_outcomes.'.$outcome) }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>
                            @if ($appealOutcome === 'modified')
                                <x-ui.input-shell :label="__('employee_relations::cases.fields.new_decision')" :error="$errors->first('appealNewDecision') ?: $errors->first('decision_outcome')" containerClass="!space-y-0" :labelClass="$labelClass">
                                    <x-ui.select wire:model="appealNewDecision">
                                        <option value="">—</option>
                                        @foreach ($this->decisionOptions() as $value => $option)
                                            <option value="{{ $value }}" @disabled($option['disabled'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.input-shell>
                            @endif
                            <x-ui.input-shell :label="__('employee_relations::cases.fields.comment')" :error="$errors->first('appealComment')" containerClass="!space-y-0 sm:col-span-3" :labelClass="$labelClass">
                                <x-ui.textarea wire:model="appealComment" :rows="2" />
                            </x-ui.input-shell>
                            <div class="flex justify-end sm:col-span-3">
                                <x-pill-button variant="primary" wire:click="resolveAppeal" wire:loading.attr="disabled" wire:target="resolveAppeal">{{ __('employee_relations::cases.actions.resolve_appeal') }}</x-pill-button>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            {{-- ===================== evidence ===================== --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.evidence') }}</h2>
                </div>
                <div class="divide-y divide-hairline-subtle">
                    @forelse ($this->evidence as $item)
                        <div class="flex flex-col gap-1 px-4 py-3" wire:key="er-evidence-{{ $item->id }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-small-badge mode="secondary">{{ __('employee_relations::cases.evidence_kinds.'.$item->kind) }}</x-small-badge>
                                <span class="text-[13px] font-medium text-ink">{{ $item->title }}</span>
                                @if ($item->providedBy)
                                    <span class="text-[11.5px] text-ink-faint">{{ __('employee_relations::cases.labels.provided_by', ['name' => $item->providedBy->fullname]) }}</span>
                                @endif
                            </div>
                            @if ($item->body)
                                <p class="whitespace-pre-line text-[12.5px] leading-5 text-ink-soft">{{ $item->body }}</p>
                            @endif
                            @if ($item->hasFile())
                                <a href="{{ route('employee-relations.evidence.download', ['evidence' => $item->id]) }}" class="text-[12.5px] font-medium text-ink underline decoration-hairline underline-offset-2 hover:decoration-ink">{{ $item->file_name }}</a>
                            @endif
                            <p class="text-[11px] text-ink-faint">{{ $item->uploader?->name ?? __('employee_relations::cases.labels.anonymous') }} · {{ $item->created_at?->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-center text-[12.5px] text-ink-faint">{{ __('employee_relations::cases.labels.no_evidence') }}</p>
                    @endforelse
                </div>

                @if ($abilities['work'] && ! $status->isClosed())
                    <div class="grid gap-3 border-t border-hairline-subtle p-4 sm:grid-cols-3">
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.evidence_kind')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model.live="evidenceKind">
                                @foreach (['file', 'explanation', 'correspondence'] as $kind)
                                    <option value="{{ $kind }}">{{ __('employee_relations::cases.evidence_kinds.'.$kind) }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.evidence_title')" :error="$errors->first('evidenceTitle')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.input wire:model="evidenceTitle" type="text" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.provided_by')" containerClass="!space-y-0" :labelClass="$labelClass">
                            <x-ui.select wire:model="evidenceProvidedBy">
                                <option value="">—</option>
                                @foreach ($subjectOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.evidence_body')" :error="$errors->first('evidenceBody')" containerClass="!space-y-0 sm:col-span-3" :labelClass="$labelClass">
                            <x-ui.textarea wire:model="evidenceBody" :rows="3" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee_relations::cases.fields.evidence_file')" :hint="__('employee_relations::cases.hints.evidence_file')" :error="$errors->first('evidenceFile') ?: $errors->first('evidence')" containerClass="!space-y-0 sm:col-span-2" :labelClass="$labelClass">
                            <input wire:model="evidenceFile" type="file"
                                class="block min-h-10 w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-1.5 text-base text-ink-muted shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-ink file:px-3 file:py-1 file:text-[14px] file:font-semibold file:text-white sm:text-sm" />
                        </x-ui.input-shell>
                        <div class="flex items-end justify-end">
                            <x-pill-button variant="primary" wire:click="addEvidence" wire:loading.attr="disabled" wire:target="addEvidence,evidenceFile">{{ __('employee_relations::cases.actions.add_evidence') }}</x-pill-button>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        <div class="flex min-w-0 flex-col gap-4">
            {{-- ===================== investigators ===================== --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.investigators') }}</h2>
                </div>
                <div class="divide-y divide-hairline-subtle">
                    @forelse ($this->investigators as $investigator)
                        <div class="flex items-center justify-between gap-2 px-4 py-2" wire:key="er-investigator-{{ $investigator->id }}">
                            <span class="truncate text-[13px] text-ink">{{ $investigator->name }}</span>
                            @if ($abilities['manage'])
                                <button type="button" class="text-[12px] text-ink-muted hover:text-rose-600" wire:click="removeInvestigator({{ $investigator->id }})">{{ __('employee_relations::cases.actions.remove') }}</button>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-4 text-[12.5px] text-ink-faint">{{ __('employee_relations::cases.labels.no_investigators') }}</p>
                    @endforelse
                </div>
                @if ($abilities['manage'] && ! $status->isClosed())
                    <div class="flex flex-col gap-2 border-t border-hairline-subtle p-4">
                        <x-ui.select wire:model="investigatorId">
                            <option value="">{{ __('employee_relations::cases.placeholders.investigator') }}</option>
                            @foreach ($this->eligibleInvestigators as $candidate)
                                <option value="{{ $candidate['id'] }}">{{ $candidate['name'] }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('investigatorId') <x-validation>{{ $message }}</x-validation> @enderror
                        @error('investigator') <x-validation>{{ $message }}</x-validation> @enderror
                        <x-pill-button wire:click="assignInvestigator" wire:loading.attr="disabled" wire:target="assignInvestigator">{{ __('employee_relations::cases.actions.assign') }}</x-pill-button>
                    </div>
                @endif
            </section>

            {{-- ===================== witnesses ===================== --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.witnesses') }}</h2>
                </div>
                <div class="divide-y divide-hairline-subtle">
                    @forelse ($this->witnesses as $witness)
                        <div class="flex items-start justify-between gap-2 px-4 py-2" wire:key="er-witness-{{ $witness->id }}">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] text-ink">{{ $witness->displayName() }}</p>
                                @if ($witness->note)
                                    <p class="text-[11.5px] text-ink-faint">{{ $witness->note }}</p>
                                @endif
                            </div>
                            @if ($abilities['work'] && ! $status->isClosed())
                                <button type="button" class="text-[12px] text-ink-muted hover:text-rose-600" wire:click="removeWitness({{ $witness->id }})">{{ __('employee_relations::cases.actions.remove') }}</button>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-4 text-[12.5px] text-ink-faint">{{ __('employee_relations::cases.labels.no_witnesses') }}</p>
                    @endforelse
                </div>
                @if ($abilities['work'] && ! $status->isClosed())
                    <div class="flex flex-col gap-2 border-t border-hairline-subtle p-4">
                        <x-ui.input icon="search" wire:model.live.debounce.300ms="witnessSearch" :placeholder="__('employee_relations::cases.placeholders.personnel_search')" />
                        @php $witnessResults = $this->personnelResults($witnessSearch); @endphp
                        @if ($witnessResults !== [])
                            <div class="overflow-hidden rounded-lg border border-hairline bg-white">
                                @foreach ($witnessResults as $result)
                                    <button type="button" wire:key="er-witness-pick-{{ $result['id'] }}" wire:click="addWitness({{ $result['id'] }})" class="flex w-full flex-col px-3 py-1.5 text-left hover:bg-[#fafafa]">
                                        <span class="text-[12.5px] font-medium text-ink">{{ $result['name'] }}</span>
                                        <span class="text-[11px] text-ink-faint">{{ $result['meta'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <x-ui.input wire:model="witnessExternalName" type="text" :placeholder="__('employee_relations::cases.placeholders.external_witness')" />
                        <x-ui.input wire:model="witnessNote" type="text" :placeholder="__('employee_relations::cases.placeholders.witness_note')" />
                        @error('witness') <x-validation>{{ $message }}</x-validation> @enderror
                        <x-pill-button wire:click="addWitness" wire:loading.attr="disabled" wire:target="addWitness">{{ __('employee_relations::cases.actions.add_external_witness') }}</x-pill-button>
                    </div>
                @endif
            </section>

            {{-- ===================== timeline ===================== --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('employee_relations::cases.sections.timeline') }}</h2>
                </div>
                <ol class="divide-y divide-hairline-subtle">
                    @foreach ($this->timeline as $event)
                        <li class="px-4 py-2.5" wire:key="er-event-{{ $event->id }}">
                            <p class="text-[12.5px] font-medium text-ink">
                                {{ __('employee_relations::cases.events.'.$event->action) }}
                                @if ($event->to_status && $event->from_status)
                                    <span class="font-normal text-ink-faint">· {{ __('employee_relations::cases.statuses.'.$event->from_status) }} → {{ __('employee_relations::cases.statuses.'.$event->to_status) }}</span>
                                @endif
                            </p>
                            @if ($event->comment)
                                <p class="mt-0.5 whitespace-pre-line text-[12px] leading-5 text-ink-soft">{{ $event->comment }}</p>
                            @endif
                            <p class="mt-0.5 text-[11px] text-ink-faint">{{ $event->actor?->name ?? __('employee_relations::cases.labels.system_or_anonymous') }} · {{ $event->created_at?->format('d.m.Y H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</div>
