<div class="rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.05)]">
    <div class="animate-pulse space-y-5">
        <div class="rounded-[1.6rem] border border-zinc-200 bg-zinc-50/70 p-4">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div class="space-y-2">
                    <div class="h-3 w-28 rounded bg-zinc-200"></div>
                    <div class="h-10 w-full rounded-2xl bg-white"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-3 w-20 rounded bg-zinc-200"></div>
                    <div class="h-10 w-full rounded-2xl bg-white"></div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <div class="h-3 w-24 rounded bg-zinc-200"></div>
                    <div class="h-11 w-full rounded-2xl bg-zinc-100"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-3 w-20 rounded bg-zinc-200"></div>
                    <div class="h-11 w-full rounded-2xl bg-zinc-100"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-3 w-20 rounded bg-zinc-200"></div>
                    <div class="h-11 w-full rounded-2xl bg-zinc-100"></div>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <div class="h-3 w-32 rounded bg-zinc-200"></div>
                    <div class="h-32 w-full rounded-[1.5rem] bg-zinc-50"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <div class="h-11 w-28 rounded-2xl bg-zinc-100"></div>
                <div class="h-11 w-36 rounded-2xl bg-zinc-200"></div>
            </div>
        </div>

        <div class="space-y-3">
            <div class="h-11 w-full rounded-2xl bg-zinc-100"></div>
            @for ($i = 0; $i < 3; $i++)
                <div class="rounded-[1.5rem] border border-zinc-200/80 bg-zinc-50/70 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-2">
                            <div class="h-4 w-40 rounded bg-zinc-200"></div>
                            <div class="h-3 w-28 rounded bg-zinc-100"></div>
                        </div>
                        <div class="h-6 w-16 rounded-full bg-zinc-100"></div>
                    </div>
                    <div class="mt-4 h-12 w-full rounded-2xl bg-white"></div>
                </div>
            @endfor
        </div>
        </div>
    </div>
</div>
