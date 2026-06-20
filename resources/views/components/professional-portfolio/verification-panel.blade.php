@props([
    'status' => 'pending',
    'verifier' => null,
    'verifiedAt' => null,
])

@php
    $mode = match ($status) {
        'verified' => 'emerald',
        'rejected' => 'rose',
        'broken_link' => 'amber',
        'archived_only' => 'sky',
        default => 'muted',
    };

    $title = match ($status) {
        'verified' => __('personnel::portfolio.messages.verification_state_verified'),
        'rejected' => __('personnel::portfolio.messages.verification_state_rejected'),
        'broken_link' => __('personnel::portfolio.messages.verification_state_broken'),
        'archived_only' => __('personnel::portfolio.messages.verification_state_archived'),
        default => __('personnel::portfolio.messages.verification_state_pending'),
    };

    $description = match ($status) {
        'verified' => __('personnel::portfolio.messages.verification_state_verified_hint'),
        'rejected' => __('personnel::portfolio.messages.verification_state_rejected_hint'),
        'broken_link' => __('personnel::portfolio.messages.verification_state_broken_hint'),
        'archived_only' => __('personnel::portfolio.messages.verification_state_archived_hint'),
        default => __('personnel::portfolio.messages.verification_state_pending_hint'),
    };
@endphp

<div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-4">
    <div class="space-y-4">
        <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
                <x-notification.chip :mode="$mode">{{ __('personnel::portfolio.status.'.$status) }}</x-notification.chip>
                <span class="text-sm font-semibold tracking-tight text-zinc-950">{{ $title }}</span>
            </div>
            <p class="text-sm leading-6 text-zinc-500">{{ $description }}</p>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.verifier') }}</p>
                <p class="mt-1 text-sm font-semibold tracking-tight text-zinc-900">{{ $verifier ?: '—' }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('personnel::portfolio.fields.verified_at') }}</p>
                <p class="mt-1 text-sm font-semibold tracking-tight text-zinc-900">{{ $verifiedAt ?: '—' }}</p>
            </div>
        </div>
    </div>
</div>
