@php
    $record = $this->record;
@endphp

<div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-gradient-to-r from-sky-50 via-white to-emerald-50 px-4 py-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <p class="text-sm font-semibold text-zinc-900">{{ __('training_needs::dashboard.labels.certificate_preview_title') }}</p>
                <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.certificate_viewer_hint') }}</p>
            </div>
            @if ($record)
                @php
                    $extension = strtoupper(pathinfo((string) ($record->certificate_name ?: $record->certificate_path), PATHINFO_EXTENSION) ?: 'FILE');
                @endphp
                <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.certificate_type_value', ['type' => $extension]) }}</x-small-badge>
            @endif
        </div>
    </div>

    <div class="space-y-4 px-4 py-4">
        @if (! $record)
            <x-ui.empty-state icon="icons.document-icon" :message="__('training_needs::dashboard.labels.certificate_preview_empty')" />
        @else
            @php
                $certificateUrl = $record->certificate_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->certificate_path) : null;
                $certificateExtension = strtolower(pathinfo((string) ($record->certificate_name ?: $record->certificate_path), PATHINFO_EXTENSION) ?: 'file');
                $isImage = in_array($certificateExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                $isPdf = $certificateExtension === 'pdf';
            @endphp

            <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.session') }}</p>
                            <p class="mt-1 text-sm font-medium text-zinc-800">{{ $record->session?->title ?? '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.personnel') }}</p>
                            <p class="mt-1 text-sm font-medium text-zinc-800">{{ $record->personnel?->fullname ?? '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.program') }}</p>
                            <p class="mt-1 text-sm font-medium text-zinc-800">{{ $record->program?->title ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-50">
                        <div class="border-b border-zinc-200 bg-white px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $record->certificate_name ?: __('training_needs::dashboard.labels.certificate_available') }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.certificate_viewer_hint') }}</p>
                                </div>
                                @if ($record->certificate_path && $certificateUrl)
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ $certificateUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                                            {{ __('training_needs::dashboard.actions.preview_certificate') }}
                                        </a>
                                        <a href="{{ $certificateUrl }}" download class="inline-flex items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-100">
                                            {{ __('training_needs::dashboard.actions.download_certificate') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="grid gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('training_needs::dashboard.labels.document_focus') }}</p>
                                <p class="mt-2 text-sm font-medium text-zinc-900">{{ __('training_needs::dashboard.labels.certificate_available') }}</p>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('training_needs::dashboard.fields.certificate_type') }}</p>
                                <p class="mt-2 text-sm font-medium text-zinc-900">{{ strtoupper($certificateExtension) }}</p>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('training_needs::dashboard.fields.status') }}</p>
                                <p class="mt-2"><x-small-badge mode="green">{{ __('training_needs::dashboard.delivery_result_statuses.'.$record->result_status) }}</x-small-badge></p>
                            </div>
                        </div>
                        <div class="flex min-h-80 items-center justify-center bg-zinc-100/80 p-4">
                            @if ($record->certificate_path && $isImage && $certificateUrl)
                                <img src="{{ $certificateUrl }}" alt="{{ $record->certificate_name }}" class="max-h-[32rem] rounded-xl border border-zinc-200 bg-white object-contain shadow-sm">
                            @elseif ($record->certificate_path && $isPdf && $certificateUrl)
                                <iframe src="{{ $certificateUrl }}" class="h-[32rem] w-full rounded-xl border border-zinc-200 bg-white"></iframe>
                            @elseif ($record->certificate_path)
                                <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-300 bg-white px-8 py-10 text-center">
                                    <x-icons.document-icon size="w-12 h-12" color="text-zinc-400" />
                                    <div>
                                        <p class="text-base font-semibold text-zinc-800">{{ strtoupper($certificateExtension) }}</p>
                                        <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.certificate_available') }}</p>
                                    </div>
                                </div>
                            @else
                                <x-ui.empty-state icon="icons.document-icon" :message="__('training_needs::dashboard.labels.certificate_preview_empty')" />
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-3xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('training_needs::dashboard.labels.certificate_metadata_title') }}</p>
                        <dl class="mt-4 space-y-3">
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500">{{ __('training_needs::dashboard.fields.certificate_name') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-zinc-900">{{ $record->certificate_name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500">{{ __('training_needs::dashboard.fields.certificate_type') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-zinc-900">{{ strtoupper($certificateExtension) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500">{{ __('training_needs::dashboard.fields.completed_at') }}</dt>
                                <dd class="mt-1 text-sm font-medium text-zinc-900">{{ optional($record->completed_at)->format('d.m.Y H:i') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500">{{ __('training_needs::dashboard.fields.status') }}</dt>
                                <dd class="mt-1"><x-small-badge mode="green">{{ __('training_needs::dashboard.statuses.'.$record->result_status) }}</x-small-badge></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-50 to-white px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('training_needs::dashboard.labels.certificate_actions_title') }}</p>
                        <div class="mt-4 space-y-2">
                            @if ($record->certificate_path && $certificateUrl)
                                <a href="{{ $certificateUrl }}" target="_blank" class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-sky-300 hover:bg-sky-50">
                                    <span>{{ __('training_needs::dashboard.actions.preview_certificate') }}</span>
                                    <x-icons.document-icon size="w-5 h-5" color="text-sky-600" />
                                </a>
                                <a href="{{ $certificateUrl }}" download class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-emerald-300 hover:bg-emerald-50">
                                    <span>{{ __('training_needs::dashboard.actions.download_certificate') }}</span>
                                    <x-icons.arrow-icon size="w-5 h-5" color="text-emerald-600" />
                                </a>
                            @endif
                            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-4 py-3 text-xs leading-6 text-zinc-500">
                                {{ __('training_needs::dashboard.labels.certificate_gallery_hint') }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200 bg-white px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('training_needs::dashboard.labels.certificate_context_title') }}</p>
                        <div class="mt-4 space-y-3 text-sm text-zinc-600">
                            <p><span class="font-semibold text-zinc-900">{{ __('training_needs::dashboard.fields.session') }}:</span> {{ $record->session?->title ?? '—' }}</p>
                            <p><span class="font-semibold text-zinc-900">{{ __('training_needs::dashboard.fields.program') }}:</span> {{ $record->program?->title ?? '—' }}</p>
                            <p><span class="font-semibold text-zinc-900">{{ __('training_needs::dashboard.fields.personnel') }}:</span> {{ $record->personnel?->fullname ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
