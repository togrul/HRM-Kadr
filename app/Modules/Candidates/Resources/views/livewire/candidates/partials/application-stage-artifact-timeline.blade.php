@if ($this->stageArtifactTimeline)
    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
            {{ __('candidates::recruitment.titles.stage_artifact_timeline') }}
        </div>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
            {{ __('candidates::recruitment.titles.stage_artifact_timeline') }}
        </h2>

        <div class="mt-6 space-y-4">
            @foreach ($this->stageArtifactTimeline as $stage)
                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $stage['label'] }}</span>
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ count($stage['assessments']) }} {{ __('candidates::recruitment.labels.assessment_records') }}</span>
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ count($stage['documents']) }} {{ __('candidates::recruitment.labels.document_records') }}</span>
                    </div>

                    @if ($stage['profile'])
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-3">
                            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                                {{ __('candidates::recruitment.titles.recruitment_context') }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-600">
                                @foreach (($stage['profile']->payload ?? []) as $key => $value)
                                    @if (filled($value))
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
                                            {{ __('candidates::recruitment.profile_fields.'.$key) }}: {{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($stage['assessments'])
                        <div class="mt-3 grid gap-2">
                            @foreach ($stage['assessments'] as $assessment)
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm">
                                    <div class="font-semibold text-slate-900">{{ __('candidates::recruitment.assessment_checklists.'.$assessment->assessment_key) }}</div>
                                    <div class="mt-1 text-slate-600">{{ __('candidates::recruitment.assessment_statuses.'.$assessment->status) }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $assessment->actor?->name ?? '—' }} · {{ optional($assessment->recorded_at)->format('d.m.Y H:i') ?? '—' }}</div>
                                    @if ($assessment->note)
                                        <div class="mt-1 text-slate-500">{{ $assessment->note }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($stage['documents'])
                        <div class="mt-3 grid gap-2">
                            @foreach ($stage['documents'] as $documentCheck)
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm">
                                    <div class="font-semibold text-slate-900">{{ __('candidates::recruitment.document_checklists.'.$documentCheck->document_key) }}</div>
                                    <div class="mt-1 text-slate-600">
                                        {{ $documentCheck->is_provided ? __('candidates::recruitment.labels.document_provided_yes') : __('candidates::recruitment.labels.document_provided_no') }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $documentCheck->actor?->name ?? '—' }} · {{ optional($documentCheck->recorded_at)->format('d.m.Y H:i') ?? '—' }}</div>
                                    @if ($documentCheck->note)
                                        <div class="mt-1 text-slate-500">{{ $documentCheck->note }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($stage['uploads'])
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($stage['uploads'] as $upload)
                                <a href="{{ route('candidates.documents.download', $upload) }}" class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">
                                    {{ $upload->display_name }}
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-400">
                            @foreach ($stage['uploads'] as $upload)
                                <span>{{ $upload->uploader?->name ?? '—' }} · {{ optional($upload->created_at)->format('d.m.Y H:i') ?? '—' }}</span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
