{{-- Skeleton for a lazy home card: same shell and height as the card it stands in for. --}}
<section class="rounded-2xl border border-hairline bg-white p-4 shadow-card" aria-busy="true">
    <div class="flex items-start justify-between gap-3">
        <div class="space-y-1.5">
            <div class="h-3.5 w-36 animate-pulse rounded bg-zinc-100"></div>
            <div class="h-3 w-24 animate-pulse rounded bg-zinc-100"></div>
        </div>
        <div class="h-3 w-16 animate-pulse rounded bg-zinc-100"></div>
    </div>

    @if ($chart)
        <div class="mt-5 flex h-44 items-end gap-2">
            @foreach ([55, 70, 40, 80, 65, 50, 75] as $height)
                <div class="w-full max-w-[44px] flex-1 animate-pulse rounded-lg bg-zinc-100" style="height: {{ $height }}%"></div>
            @endforeach
        </div>
    @else
        <div class="mt-3 space-y-3">
            @for ($row = 0; $row < $rows; $row++)
                <div class="flex items-center gap-3">
                    <div class="h-7 w-7 shrink-0 animate-pulse rounded-full bg-zinc-100"></div>
                    <div class="h-3 flex-1 animate-pulse rounded bg-zinc-100"></div>
                </div>
            @endfor
        </div>
    @endif
</section>
