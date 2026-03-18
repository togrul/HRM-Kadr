@php
    $record = $this->record;
@endphp

<div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-gradient-to-r from-sky-50 via-white to-emerald-50 px-5 py-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-1">
                <p class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('training_needs::dashboard.labels.certificate_preview_title') }}</p>
                <p class="max-w-2xl text-sm leading-6 text-zinc-600">{{ __('training_needs::dashboard.labels.certificate_viewer_hint') }}</p>
            </div>
            @if ($record)
                @php
                    $extension = strtoupper(pathinfo((string) ($record->certificate_name ?: $record->certificate_path), PATHINFO_EXTENSION) ?: 'FILE');
                @endphp
                <div class="flex flex-wrap items-center gap-2">
                    <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.certificate_type_value', ['type' => $extension]) }}</x-small-badge>
                    <x-small-badge mode="green" class="uppercase font-semibold">{{ __('training_needs::dashboard.delivery_result_statuses.'.$record->result_status) }}</x-small-badge>
                </div>
            @endif
        </div>
    </div>

    <div class="px-5 py-5">
        @if (! $record)
            <x-ui.empty-state
                icon="icons.document-icon"
                :title="__('training_needs::dashboard.labels.certificate_preview_title')"
                :message="__('training_needs::dashboard.labels.certificate_preview_empty')"
            />
        @else
            @php
                $hasCertificateFile = filled($record->certificate_path);
                $hasPendingUpload = (bool) $this->hasPendingUpload;
                $certificateUrl = $hasPendingUpload
                    ? $this->temporaryCertificatePreviewUrl
                    : ($hasCertificateFile ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->certificate_path) : null);
                $certificateExtension = $hasPendingUpload
                    ? $this->temporaryCertificateExtension
                    : ($hasCertificateFile
                    ? strtolower(pathinfo((string) ($record->certificate_name ?: $record->certificate_path), PATHINFO_EXTENSION) ?: 'file')
                    : null);
                $isImage = in_array($certificateExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                $isPdf = $certificateExtension === 'pdf';
                $completedAtRaw = $record->completed_at ?? null;
                $completedAt = $completedAtRaw instanceof \Carbon\CarbonInterface
                    ? $completedAtRaw->format('d.m.Y H:i')
                    : ($completedAtRaw ? \Illuminate\Support\Carbon::parse($completedAtRaw)->format('d.m.Y H:i') : '—');
                $certificateName = $hasPendingUpload
                    ? ($this->temporaryCertificateName ?: '—')
                    : ($record->certificate_name
                        ?: ($hasCertificateFile
                            ? (basename((string) $record->certificate_path) ?: __('training_needs::dashboard.labels.certificate_available'))
                            : '—'));
            @endphp

            <div class="space-y-5">
                <div class="grid gap-3 lg:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.session') }}</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-zinc-900 break-words">{{ $record->session?->title ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.personnel') }}</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-zinc-900 break-words">{{ $record->personnel?->fullname ?? '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('training_needs::dashboard.fields.program') }}</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-zinc-900 break-words">{{ $record->program?->title ?? '—' }}</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[28px] border border-zinc-200 bg-zinc-50/70">
                    <div class="border-b border-zinc-200 bg-white/90 px-5 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <p class="break-words text-base font-semibold tracking-tight text-zinc-950">{{ $certificateName }}</p>
                                <p class="mt-1 text-sm leading-6 text-zinc-600">
                                    {{ $hasPendingUpload
                                        ? __('training_needs::dashboard.labels.certificate_pending_hint')
                                        : __('training_needs::dashboard.labels.certificate_gallery_hint') }}
                                </p>
                            </div>
                            @if (($hasCertificateFile || $hasPendingUpload) && $certificateUrl)
                                <div class="flex flex-wrap gap-2">
                                    @if ($hasPendingUpload && $certificateUrl)
                                        <span class="flex items-center justify-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs uppercase tracking-tighter text-center font-semibold text-amber-700">
                                            {{ __('training_needs::dashboard.labels.certificate_unsaved') }}
                                        </span>
                                    @endif
                                    @if ($hasCertificateFile && ! $hasPendingUpload)
                                        <a href="{{ $certificateUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                                            {{ __('training_needs::dashboard.actions.preview_certificate') }}
                                        </a>
                                        <a href="{{ $certificateUrl }}" download class="inline-flex items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-100">
                                            {{ __('training_needs::dashboard.actions.download_certificate') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="{{ $hasCertificateFile || $hasPendingUpload ? 'flex min-h-[24rem] items-center justify-center bg-zinc-100/80 p-4 sm:p-5' : 'bg-zinc-100/60 p-4 sm:p-5' }}">
                        @if (($hasCertificateFile || $hasPendingUpload) && $isImage && $certificateUrl)
                            <img src="{{ $certificateUrl }}" alt="{{ $record->certificate_name }}" class="max-h-[34rem] w-full rounded-2xl border border-zinc-200 bg-white object-contain shadow-sm">
                        @elseif (($hasCertificateFile || $hasPendingUpload) && $isPdf && $certificateUrl)
                            <iframe src="{{ $certificateUrl }}" class="h-[34rem] w-full rounded-2xl border border-zinc-200 bg-white"></iframe>
                        @elseif ($hasCertificateFile || $hasPendingUpload)
                            <div class="flex w-full max-w-xl flex-col items-center gap-4 rounded-3xl border border-dashed border-zinc-300 bg-white px-8 py-10 text-center shadow-sm">
                                <span class="inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-zinc-100 text-zinc-500">
                                    <x-icons.document-icon size="w-8 h-8" color="text-zinc-500" />
                                </span>
                                <div class="space-y-2">
                                    <p class="text-lg font-semibold tracking-tight text-zinc-900">{{ strtoupper($certificateExtension) }}</p>
                                    <p class="break-words text-sm font-medium text-zinc-700">{{ $record->certificate_name ?: __('training_needs::dashboard.labels.certificate_available') }}</p>
                                    <p class="text-sm leading-6 text-zinc-500">{{ __('training_needs::dashboard.labels.certificate_gallery_hint') }}</p>
                                </div>
                            </div>
                        @else
                            <x-ui.empty-state
                                icon="icons.document-icon"
                                :title="__('training_needs::dashboard.labels.certificate_preview_title')"
                                :message="__('training_needs::dashboard.labels.certificate_preview_empty')"
                                class="mx-auto w-full max-w-xl border-none bg-transparent px-0 py-10"
                            />
                        @endif
                    </div>
                </div>

                <div class="grid">
                    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50/70 px-5 py-5">
                        <p class="text-sm font-semibold tracking-tight text-zinc-500">{{ __('training_needs::dashboard.labels.certificate_metadata_title') }}</p>
                        <dl class="mt-3 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
                            <div class="grid gap-2 border-b border-zinc-200 px-4 py-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-start">
                                <dt class="text-xs font-semibold uppercase tracking-tight text-zinc-500">{{ __('training_needs::dashboard.fields.certificate_name') }}</dt>
                                <dd class="text-sm font-semibold leading-6 text-zinc-900 break-words">{{ $certificateName }}</dd>
                            </div>
                            <div class="grid gap-2 border-b border-zinc-200 px-4 py-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-start">
                                <dt class="text-xs font-semibold uppercase tracking-tight text-zinc-500">{{ __('training_needs::dashboard.fields.certificate_type') }}</dt>
                                <dd class="text-sm font-semibold text-zinc-900">{{ $certificateExtension ? strtoupper($certificateExtension) : '—' }}</dd>
                            </div>
                            <div class="grid gap-2 border-b border-zinc-200 px-4 py-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-start">
                                <dt class="text-xs font-semibold uppercase tracking-tight text-zinc-500">{{ __('training_needs::dashboard.fields.completed_at') }}</dt>
                                <dd class="text-sm font-semibold leading-6 text-zinc-900">{{ $completedAt }}</dd>
                            </div>
                            <div class="grid gap-2 px-4 py-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-start">
                                <dt class="text-xs font-semibold uppercase tracking-tight text-zinc-500">{{ __('training_needs::dashboard.fields.status') }}</dt>
                                <dd><x-small-badge class="uppercase font-semibold" mode="green">{{ __('training_needs::dashboard.delivery_result_statuses.'.$record->result_status) }}</x-small-badge></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
