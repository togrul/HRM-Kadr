<section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
    <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
        {{ __('candidates::recruitment.titles.stage_timeline') }}
    </div>
    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
        {{ __('candidates::recruitment.titles.stage_timeline') }}
    </h2>

    <div class="mt-6 space-y-4">
        @foreach ($application->stageEvents as $event)
            @php
                $audit = is_array($event->payload['audit'] ?? null) ? $event->payload['audit'] : [];
                $profileFieldKeys = collect($audit['profile_field_keys'] ?? [])->filter()->values();
            @endphp
            <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ __('candidates::recruitment.stages.'.$event->stage_key) }}</span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">{{ $event->action }}</span>
                    @if (! empty($audit['from_stage']) || ! empty($audit['to_stage']))
                        <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                            {{ __('candidates::recruitment.stages.'.($audit['from_stage'] ?? $event->stage_key)) }} → {{ __('candidates::recruitment.stages.'.($audit['to_stage'] ?? $event->stage_key)) }}
                        </span>
                    @endif
                </div>
                <div class="mt-3 text-sm font-semibold text-slate-900">{{ $event->actor?->name ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ optional($event->occurred_at)->format('d.m.Y H:i') ?? '—' }}</div>
                @if ($audit !== [])
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if (($audit['assessment_total'] ?? 0) > 0)
                            <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ __('candidates::recruitment.labels.assessment_records') }}: {{ $audit['assessment_passed'] ?? 0 }}/{{ $audit['assessment_total'] }}
                            </span>
                        @endif
                        @if (($audit['document_total'] ?? 0) > 0)
                            <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ __('candidates::recruitment.labels.document_records') }}: {{ $audit['document_provided'] ?? 0 }}/{{ $audit['document_total'] }}
                            </span>
                        @endif
                        @if ($profileFieldKeys->isNotEmpty())
                            <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ __('candidates::recruitment.labels.profile_field_changes') }}: {{ $profileFieldKeys->count() }}
                            </span>
                        @endif
                    </div>
                @endif
                @if ($event->decision || $event->score || $event->note)
                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        @if ($event->decision)
                            <div>{{ __('candidates::recruitment.labels.decision') }}: {{ $event->decision }}</div>
                        @endif
                        @if ($event->score !== null)
                            <div>{{ __('candidates::recruitment.labels.score') }}: {{ $event->score }}</div>
                        @endif
                        @if ($event->note)
                            <div>{{ $event->note }}</div>
                        @endif
                    </div>
                @endif
                @if ($profileFieldKeys->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($profileFieldKeys as $key)
                            @php
                                $translated = __('candidates::recruitment.profile_fields.'.$key);
                                $fallback = $translated !== 'candidates::recruitment.profile_fields.'.$key
                                    ? $translated
                                    : __('candidates::common.labels.'.$key);
                            @endphp
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-500">
                                {{ $fallback !== 'candidates::common.labels.'.$key ? $fallback : $key }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>
