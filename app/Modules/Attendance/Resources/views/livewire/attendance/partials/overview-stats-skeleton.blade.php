{{-- Skeleton for the deferred overview statistics: same sections, grids and tile shells as the real block. --}}
<div class="space-y-4" aria-busy="true">
    @foreach ([['tiles' => 5, 'grid' => 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5'], ['tiles' => 4, 'grid' => 'sm:grid-cols-2 xl:grid-cols-4']] as $section)
        <section class="space-y-3">
            <div class="h-3 w-40 animate-pulse rounded bg-zinc-100"></div>
            <div class="grid gap-3 {{ $section['grid'] }}">
                @for ($tile = 0; $tile < $section['tiles']; $tile++)
                    <div class="flex flex-col rounded-2xl border border-hairline bg-white px-4 py-3.5 shadow-card">
                        <div class="h-3 w-24 animate-pulse rounded bg-zinc-100"></div>
                        <div class="mt-3 h-6 w-16 animate-pulse rounded bg-zinc-100"></div>
                    </div>
                @endfor
            </div>
        </section>
    @endforeach
</div>
