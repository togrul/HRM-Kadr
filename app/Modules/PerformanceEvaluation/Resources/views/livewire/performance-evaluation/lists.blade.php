@php
    $d = 'performance_evaluation::dashboard';
    $th = 'px-4 py-2.5';
    $td = 'px-4 py-3 text-ink-soft';
    $tdMain = 'px-4 py-3 font-medium text-ink';
    $chip = 'inline-flex max-w-full items-center gap-1 truncate rounded-md bg-[#f4f4f5] px-2 py-1 text-[11.5px] text-ink-muted';
    $detailBox = 'rounded-xl border border-hairline-subtle bg-[#fafafa] px-3.5 py-3';
    $detailLabel = 'hrm-eyebrow';
    $entities = [
        'forms' => 'cards.recent_forms',
        'templates' => 'cards.recent_templates',
        'items' => 'cards.recent_template_items',
        'test_banks' => 'cards.recent_test_banks',
        'test_questions' => 'cards.test_question_setup',
        'test_sessions' => 'cards.test_session_setup',
        'attempts' => 'cards.recent_test_attempts',
        'test_answers' => 'cards.answer_audit',
        'weak_links' => 'cards.weak_links',
    ];
    $selected = $this->selectedRow;
    $activePill = fn (bool $on) => $on ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted';
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-4">
    <div class="flex flex-col gap-3">
        <div>
            <p class="text-[15px] font-semibold tracking-[-0.01em] text-ink">{{ __($d.'.cards.full_lists') }}</p>
            <p class="mt-0.5 text-[12.5px] text-ink-muted">{{ __($d.'.labels.full_lists_hint') }}</p>
        </div>

        <x-filter.nav wrap class="min-w-0">
            @foreach ($entities as $key => $label)
                <x-filter.item wire:key="lists-entity-{{ $key }}" wire:click.prevent="switchEntity('{{ $key }}')" :active="$entity === $key">
                    {{ __($d.'.'.$label) }}
                </x-filter.item>
            @endforeach
        </x-filter.nav>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.85fr)]">
        <div class="flex min-w-0 flex-col gap-3">
            {{-- filters --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto]">
                <div class="flex h-11 items-center gap-2 rounded-xl border border-hairline bg-white px-3 shadow-card focus-within:border-zinc-400">
                    <svg class="h-4 w-4 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <label for="performance-lists-search" class="sr-only">{{ __($d.'.fields.search') }}</label>
                    <input id="performance-lists-search" type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __($d.'.fields.search') }}"
                        class="h-full w-full border-0 bg-transparent px-0 text-[13px] text-ink placeholder:text-ink-faint focus:outline-none focus:ring-0">
                </div>
                <div>
                    <label for="performance-lists-filter" class="sr-only">{{ __($d.'.fields.status_filter') }}</label>
                    <select id="performance-lists-filter" wire:model.live="filter" class="h-11 w-full rounded-xl border border-hairline bg-white px-3 text-[13px] text-ink shadow-card focus:border-zinc-400 focus:outline-none focus:ring-0">
                        @foreach ($this->filterOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex h-11 items-center gap-2 rounded-xl border border-hairline bg-white px-3.5 shadow-card">
                    <span class="hrm-num text-[18px] font-semibold text-ink">{{ $this->summary['visible'] }}</span>
                    <span class="text-[11.5px] leading-tight text-ink-faint">{{ __($d.'.labels.visible_records') }}<br>{{ __($d.'.labels.total_records_value', ['count' => $this->summary['total']]) }}</span>
                </div>
            </div>

            {{-- table --}}
            <div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
                <div class="hrm-scroll overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-[13px]">
                        <thead class="hrm-eyebrow whitespace-nowrap border-b border-hairline-subtle bg-[#fafafa]">
                            <tr>
                                @if ($entity === 'forms')
                                    <th class="{{ $th }}">{{ __($d.'.fields.personnel') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.cycle') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.template') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.final_category') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.score') }}</th>
                                @elseif ($entity === 'templates')
                                    <th class="{{ $th }}">{{ __($d.'.fields.template_name') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.template_code') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.status') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.labels.sections_count', ['count' => '']) }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.description') }}</th>
                                @elseif ($entity === 'items')
                                    <th class="{{ $th }}">{{ __($d.'.fields.item') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.section') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.template') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.competency') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.low_score_threshold') }}</th>
                                @elseif ($entity === 'test_banks')
                                    <th class="{{ $th }}">{{ __($d.'.fields.test_bank') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.template_code') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.status') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.question_count') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.session_count') }}</th>
                                @elseif ($entity === 'test_questions')
                                    <th class="{{ $th }}">{{ __($d.'.fields.prompt') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.test_bank') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.question_type') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.competency') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.answers_count') }}</th>
                                @elseif ($entity === 'test_sessions')
                                    <th class="{{ $th }}">#</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.personnel') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.test_bank') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.reviewer') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.status') }}</th>
                                @elseif ($entity === 'attempts')
                                    <th class="{{ $th }}">#</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.personnel') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.test_bank') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.status') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.score') }}</th>
                                @elseif ($entity === 'test_answers')
                                    <th class="{{ $th }}">#</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.personnel') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.question_type') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.review_status') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.final_score') }}</th>
                                @else
                                    <th class="{{ $th }}">{{ __($d.'.fields.competency') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.personnel') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.priority') }}</th>
                                    <th class="{{ $th }}">{{ __($d.'.fields.status') }}</th>
                                    <th class="{{ $th }} text-right">{{ __($d.'.fields.links_count') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline-subtle">
                            @forelse ($this->rows as $row)
                                <tr wire:key="lists-row-{{ $entity }}-{{ $row->id }}" wire:click="selectRow({{ $row->id }})"
                                    class="cursor-pointer align-top transition-colors {{ $selectedRowId === $row->id ? 'bg-[#f4f4f5] shadow-[inset_3px_0_0_#18181b]' : 'hover:bg-[#fafafa]' }}">
                                    @if ($entity === 'forms')
                                        <td class="{{ $tdMain }}">{{ $row->personnel?->fullname ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->cycle?->name ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->template?->name ?: $row->template?->code ?: '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->final_category ? __($d.'.categories.'.$row->final_category) : '—' }}</td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->final_score ?? '—' }}</td>
                                    @elseif ($entity === 'templates')
                                        <td class="{{ $tdMain }}">{{ $row->name }}</td>
                                        <td class="{{ $td }} hrm-num">{{ $row->code ?: '—' }}</td>
                                        <td class="{{ $td }}"><span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $activePill((bool) $row->is_active) }}"><span class="h-1.5 w-1.5 rounded-full {{ $row->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $row->is_active ? __($d.'.labels.active') : __($d.'.labels.inactive') }}</span></td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->sections_count }}</td>
                                        <td class="{{ $td }} text-ink-muted">{{ \Illuminate\Support\Str::limit((string) $row->description, 80) ?: '—' }}</td>
                                    @elseif ($entity === 'items')
                                        <td class="{{ $tdMain }}">{{ $row->name }}</td>
                                        <td class="{{ $td }}">{{ $row->section?->name ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->section?->template?->name ?: $row->section?->template?->code ?: '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->low_score_threshold }}</td>
                                    @elseif ($entity === 'test_banks')
                                        <td class="{{ $tdMain }}">{{ $row->name }}</td>
                                        <td class="{{ $td }} hrm-num">{{ $row->code ?: '—' }}</td>
                                        <td class="{{ $td }}"><span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-[11.5px] font-medium {{ $activePill((bool) $row->is_active) }}"><span class="h-1.5 w-1.5 rounded-full {{ $row->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $row->is_active ? __($d.'.labels.active') : __($d.'.labels.inactive') }}</span></td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->questions_count }}</td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->sessions_count }}</td>
                                    @elseif ($entity === 'test_questions')
                                        <td class="{{ $tdMain }}">{{ \Illuminate\Support\Str::limit((string) $row->prompt, 80) }}</td>
                                        <td class="{{ $td }}">{{ $row->bank?->name ?: $row->bank?->code ?: '—' }}</td>
                                        <td class="{{ $td }}">{{ __($d.'.question_types.'.$row->question_type) }}</td>
                                        <td class="{{ $td }}">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->answers_count }}</td>
                                    @elseif ($entity === 'test_sessions')
                                        <td class="{{ $td }} hrm-num text-ink-faint">#{{ $row->id }}</td>
                                        <td class="{{ $tdMain }}">{{ $row->personnel?->fullname ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->bank?->name ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->reviewer?->name ?: $row->reviewer?->email ?: '—' }}</td>
                                        <td class="{{ $td }}">{{ __($d.'.test_statuses.'.$row->status) }}</td>
                                    @elseif ($entity === 'attempts')
                                        <td class="{{ $td }} hrm-num text-ink-faint">#{{ $row->id }}</td>
                                        <td class="{{ $tdMain }}">{{ $row->session?->personnel?->fullname ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->session?->bank?->name ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ __($d.'.test_statuses.'.$row->status) }}</td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->score ?? '—' }}</td>
                                    @elseif ($entity === 'test_answers')
                                        <td class="{{ $td }} hrm-num text-ink-faint">#{{ $row->id }}</td>
                                        <td class="{{ $tdMain }}">{{ $row->attempt?->session?->personnel?->fullname ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ __($d.'.question_types.'.$row->question?->question_type) }}</td>
                                        <td class="{{ $td }}">{{ $row->review_status ? __($d.'.review_statuses.'.$row->review_status) : '—' }}</td>
                                        <td class="{{ $td }} hrm-num text-right">{{ $row->final_score ?? $row->review_score ?? $row->auto_score ?? '—' }}</td>
                                    @else
                                        <td class="{{ $tdMain }}">{{ $row->competency?->name ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ $row->form?->personnel?->fullname ?? '—' }}</td>
                                        <td class="{{ $td }}">{{ __('training_needs::dashboard.priorities.'.($row->trainingNeed?->priority ?? 'medium')) }}</td>
                                        <td class="{{ $td }}">{{ __('training_needs::dashboard.need_statuses.'.($row->trainingNeed?->status ?? 'draft')) }}</td>
                                        <td class="{{ $td }} hrm-num text-right">1</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-14 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.full_lists') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-hairline-subtle px-4 py-3">
                    {{ $this->rows->links() }}
                </div>
            </div>
        </div>

        {{-- detail panel --}}
        <aside class="self-start overflow-hidden rounded-2xl border border-hairline bg-white shadow-card xl:sticky xl:top-4">
            @if ($selected)
                <div class="border-b border-hairline-subtle bg-[#fafafa] px-5 py-3">
                    <p class="{{ $detailLabel }}">{{ __($d.'.labels.detail_panel') }}</p>
                    <p class="mt-1 text-[16px] font-semibold leading-snug tracking-[-0.02em] text-ink">
                        @if ($entity === 'forms')
                            {{ $selected->personnel?->fullname ?? '—' }}
                        @elseif (in_array($entity, ['templates', 'items', 'test_banks'], true))
                            {{ $selected->name ?: '—' }}
                        @elseif ($entity === 'test_questions')
                            {{ \Illuminate\Support\Str::limit((string) $selected->prompt, 80) }}
                        @elseif (in_array($entity, ['test_sessions', 'attempts', 'test_answers'], true))
                            <span class="hrm-num">#{{ $selected->id }}</span>
                        @else
                            {{ $selected->competency?->name ?? '—' }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-col gap-3 px-5 py-4">
                    @if ($entity !== 'test_answers')
                        <div class="flex flex-wrap gap-1.5">
                            @if ($entity === 'forms')
                                @php $category = $selected->final_category; @endphp
                                <span class="{{ $chip }}">{{ __($d.'.fields.cycle') }}: {{ $selected->cycle?->name ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.template') }}: {{ $selected->template?->name ?: $selected->template?->code ?: '—' }}</span>
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11.5px] {{ $category === 'weak' ? 'bg-rose-50 text-rose-700' : ($category === 'high' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700') }}">{{ __($d.'.fields.final_category') }}: {{ $category ? __($d.'.categories.'.$category) : '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.score') }}: <span class="hrm-num text-ink">{{ $selected->final_score ?? '—' }}</span></span>
                            @elseif ($entity === 'templates')
                                <span class="{{ $chip }}">{{ __($d.'.fields.template_code') }}: <span class="hrm-num">{{ $selected->code ?: '—' }}</span></span>
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11.5px] {{ $activePill((bool) $selected->is_active) }}">{{ $selected->is_active ? __($d.'.labels.active') : __($d.'.labels.inactive') }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.labels.sections_count', ['count' => $selected->sections_count]) }}</span>
                            @elseif ($entity === 'items')
                                <span class="{{ $chip }}">{{ __($d.'.fields.section') }}: {{ $selected->section?->name ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.template') }}: {{ $selected->section?->template?->name ?: $selected->section?->template?->code ?: '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.weight_percent') }}: <span class="hrm-num text-ink">{{ number_format((float) $selected->weight_percent, 2) }}%</span></span>
                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-[11.5px] text-amber-700">{{ __($d.'.fields.low_score_threshold') }}: <span class="hrm-num">{{ number_format((float) $selected->low_score_threshold, 2) }}</span></span>
                            @elseif ($entity === 'test_banks')
                                <span class="{{ $chip }}">{{ __($d.'.fields.template_code') }}: <span class="hrm-num">{{ $selected->code ?: '—' }}</span></span>
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11.5px] {{ $activePill((bool) $selected->is_active) }}">{{ $selected->is_active ? __($d.'.labels.active') : __($d.'.labels.inactive') }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.question_count') }}: <span class="hrm-num text-ink">{{ $selected->questions_count }}</span></span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.session_count') }}: <span class="hrm-num text-ink">{{ $selected->sessions_count }}</span></span>
                            @elseif ($entity === 'test_questions')
                                <span class="{{ $chip }}">{{ __($d.'.fields.test_bank') }}: {{ $selected->bank?->name ?: $selected->bank?->code ?: '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.competency') }}: {{ $selected->competency?->name ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.question_type') }}: {{ __($d.'.question_types.'.$selected->question_type) }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.answers_count') }}: <span class="hrm-num text-ink">{{ $selected->answers_count }}</span></span>
                            @elseif ($entity === 'test_sessions')
                                <span class="{{ $chip }}">{{ __($d.'.fields.personnel') }}: {{ $selected->personnel?->fullname ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.test_bank') }}: {{ $selected->bank?->name ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.status') }}: {{ __($d.'.test_statuses.'.$selected->status) }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.attempts_count') }}: <span class="hrm-num text-ink">{{ $selected->attempts_count }}</span></span>
                            @elseif ($entity === 'attempts')
                                <span class="{{ $chip }}">{{ __($d.'.fields.personnel') }}: {{ $selected->session?->personnel?->fullname ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.test_bank') }}: {{ $selected->session?->bank?->name ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.status') }}: {{ __($d.'.test_statuses.'.$selected->status) }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.score') }}: <span class="hrm-num text-ink">{{ $selected->score ?? '—' }}</span></span>
                            @else
                                <span class="{{ $chip }}">{{ __($d.'.fields.personnel') }}: {{ $selected->form?->personnel?->fullname ?? '—' }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.priority') }}: {{ __('training_needs::dashboard.priorities.'.($selected->trainingNeed?->priority ?? 'medium')) }}</span>
                                <span class="{{ $chip }}">{{ __($d.'.fields.status') }}: {{ __('training_needs::dashboard.need_statuses.'.($selected->trainingNeed?->status ?? 'draft')) }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($entity === 'templates')
                        <div class="{{ $detailBox }} text-[13px] leading-6 text-ink-soft">{{ $selected->description ?: '—' }}</div>
                    @elseif ($entity === 'weak_links')
                        <div class="{{ $detailBox }} text-[13px] leading-6 text-ink-soft">{{ $selected->trainingNeed?->presentedReason() ?? '—' }}</div>
                    @elseif ($entity === 'items')
                        <div class="{{ $detailBox }} text-[13px] leading-6 text-ink-soft">{{ $selected->competency?->name ?? __($d.'.labels.no_competency') }}</div>
                    @elseif ($entity === 'test_banks')
                        <div class="{{ $detailBox }}">
                            <p class="{{ $detailLabel }}">{{ __($d.'.fields.description') }}</p>
                            <p class="mt-1 text-[13px] leading-6 text-ink-soft">{{ $selected->description ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="{{ $detailLabel }} mb-2">{{ __($d.'.cards.test_bank_contents') }}</p>
                            <div class="flex flex-col divide-y divide-hairline-subtle rounded-xl border border-hairline-subtle">
                                @forelse ($selected->questions as $question)
                                    <div wire:key="lists-bank-question-{{ $question->id }}" class="px-3.5 py-2.5">
                                        <p class="text-[13px] font-medium text-ink">{{ \Illuminate\Support\Str::limit((string) $question->prompt, 90) }}</p>
                                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __($d.'.question_types.'.$question->question_type) }} · {{ $question->competency?->name ?? __($d.'.labels.no_competency') }}</p>
                                    </div>
                                @empty
                                    <p class="px-3.5 py-4 text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_questions') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @elseif ($entity === 'test_questions')
                        <div class="{{ $detailBox }}">
                            <p class="{{ $detailLabel }}">{{ __($d.'.fields.prompt') }}</p>
                            <p class="mt-1 text-[13px] leading-6 text-ink">{{ $selected->prompt ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="{{ $detailLabel }} mb-2">{{ __($d.'.fields.options_text') }}</p>
                            <div class="flex flex-col divide-y divide-hairline-subtle rounded-xl border border-hairline-subtle">
                                @forelse ($selected->options as $option)
                                    <div wire:key="lists-option-{{ $option->id }}" class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                                        <p class="min-w-0 text-[13px] font-medium text-ink">{{ $option->label }}</p>
                                        <div class="flex shrink-0 items-center gap-2 text-[11.5px] text-ink-faint">
                                            <span>{{ __($d.'.fields.score') }}: <span class="hrm-num text-ink">{{ $option->score_value }}</span></span>
                                            <span class="rounded-md px-1.5 py-0.5 {{ $option->is_correct ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f4f4f5] text-ink-muted' }}">{{ __($d.'.fields.is_correct') }}: {{ $option->is_correct ? __($d.'.labels.yes') : __($d.'.labels.no') }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="px-3.5 py-4 text-[12.5px] text-ink-faint">—</p>
                                @endforelse
                            </div>
                        </div>
                    @elseif ($entity === 'test_sessions')
                        <dl class="grid grid-cols-2 gap-2">
                            @foreach ([
                                'cycle' => $selected->cycle?->name ?? '—',
                                'reviewer' => $selected->reviewer?->name ?: $selected->reviewer?->email ?: '—',
                                'scheduled_at' => $selected->scheduled_at?->format('d.m.Y') ?? '—',
                                'available_until' => $selected->available_until?->format('d.m.Y') ?? '—',
                            ] as $field => $value)
                                <div class="{{ $detailBox }}">
                                    <dt class="{{ $detailLabel }}">{{ __($d.'.fields.'.$field) }}</dt>
                                    <dd class="mt-1 truncate text-[13px] font-medium text-ink {{ in_array($field, ['scheduled_at', 'available_until'], true) ? 'hrm-num' : '' }}">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @elseif ($entity === 'test_answers')
                        <div class="grid grid-cols-2 gap-2">
                            <div class="{{ $detailBox }} min-w-0">
                                <p class="{{ $detailLabel }}">{{ __($d.'.fields.personnel') }}</p>
                                <p class="mt-1 text-[13px] font-semibold leading-snug text-ink" title="{{ $selected->attempt?->session?->personnel?->fullname ?? '—' }}">{{ $selected->attempt?->session?->personnel?->fullname ?? '—' }}</p>
                            </div>
                            <div class="{{ $detailBox }} min-w-0">
                                <p class="{{ $detailLabel }}">{{ __($d.'.fields.test_bank') }}</p>
                                <p class="mt-1 text-[13px] font-semibold leading-snug text-ink" title="{{ $selected->attempt?->session?->bank?->name ?? '—' }}">{{ $selected->attempt?->session?->bank?->name ?? '—' }}</p>
                            </div>
                            <div class="{{ $detailBox }}">
                                <p class="{{ $detailLabel }}">{{ __($d.'.fields.review_status') }}</p>
                                <p class="mt-1 text-[13px] font-semibold text-ink">{{ $selected->review_status ? __($d.'.review_statuses.'.$selected->review_status) : '—' }}</p>
                            </div>
                            <div class="{{ $detailBox }}">
                                <p class="{{ $detailLabel }}">{{ __($d.'.fields.final_score') }}</p>
                                <p class="hrm-num mt-1 text-[22px] font-semibold leading-none text-ink">{{ $selected->final_score ?? $selected->review_score ?? $selected->auto_score ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="{{ $detailBox }}">
                            <p class="{{ $detailLabel }}">{{ __($d.'.fields.prompt') }}</p>
                            <p class="mt-1 text-[13.5px] font-medium leading-6 text-ink">{{ $selected->question?->prompt ?? '—' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="{{ $detailBox }}">
                                <p class="{{ $detailLabel }}">{{ __($d.'.fields.option') }}</p>
                                <p class="mt-1 text-[13px] leading-6 text-ink">{{ $selected->selectedOption?->label ?? '—' }}</p>
                            </div>
                            <div class="{{ $detailBox }}">
                                <p class="{{ $detailLabel }}">{{ __($d.'.fields.answer_text') }}</p>
                                <p class="mt-1 text-[13px] leading-6 text-ink">{{ $selected->answer_text ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="{{ $detailBox }}">
                            <p class="{{ $detailLabel }}">{{ __($d.'.fields.feedback') }}</p>
                            <p class="mt-1 text-[13px] leading-6 text-ink">{{ $selected->feedback ?: '—' }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f4f4f5] text-ink-faint">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    </div>
                    <p class="mx-auto mt-3 max-w-xs text-[12.5px] leading-5 text-ink-faint">{{ __($d.'.empty.select_list_row') }}</p>
                </div>
            @endif
        </aside>
    </div>
</div>
