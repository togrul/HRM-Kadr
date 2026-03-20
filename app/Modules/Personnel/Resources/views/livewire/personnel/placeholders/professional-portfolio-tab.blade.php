<div class="space-y-6">
    <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-4">
        @foreach (range(1, 4) as $index)
            <div class="space-y-2">
                <div class="h-4 w-20 animate-pulse rounded-full bg-zinc-100"></div>
                <div class="h-[50px] animate-pulse rounded-2xl border border-zinc-200 bg-zinc-50"></div>
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between gap-3">
        <div class="h-5 w-28 animate-pulse rounded-full bg-zinc-100"></div>
        <div class="h-11 w-40 animate-pulse rounded-2xl bg-zinc-950/10"></div>
    </div>

    <div class="space-y-3">
        @foreach (range(1, 2) as $index)
            <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-4">
                <div class="space-y-3">
                    <div class="h-14 animate-pulse rounded-[22px] border border-zinc-200 bg-white"></div>
                    <div class="flex flex-wrap gap-2">
                        <div class="h-8 w-24 animate-pulse rounded-full bg-zinc-100"></div>
                        <div class="h-8 w-24 animate-pulse rounded-full bg-zinc-100"></div>
                        <div class="h-8 w-24 animate-pulse rounded-full bg-zinc-100"></div>
                    </div>
                    <div class="border-t border-zinc-100"></div>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach (range(1, 4) as $buttonIndex)
                            <div class="h-10 animate-pulse rounded-2xl border border-zinc-200 bg-white"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
