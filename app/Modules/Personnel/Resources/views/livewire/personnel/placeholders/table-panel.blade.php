<div class="space-y-4">
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <div class="h-12 border-b border-zinc-200 bg-zinc-50 animate-pulse"></div>
        @for ($row = 0; $row < 4; $row++)
            <div class="grid grid-cols-6 gap-3 px-4 py-4 border-b border-zinc-100">
                @for ($col = 0; $col < 6; $col++)
                    <div class="h-5 rounded bg-zinc-100 animate-pulse"></div>
                @endfor
            </div>
        @endfor
    </div>
    <div class="h-10 rounded-lg bg-zinc-100 animate-pulse"></div>
</div>
