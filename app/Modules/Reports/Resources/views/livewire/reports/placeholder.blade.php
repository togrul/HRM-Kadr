<div class="space-y-5 animate-pulse px-4 py-3 lg:px-5">
    <div class="overflow-hidden rounded-[2rem] border border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
        <div class="border-b border-zinc-200 px-5 py-4 lg:px-6">
            <div class="h-3 w-32 rounded-full bg-zinc-100"></div>
            <div class="mt-3 h-7 w-64 rounded-full bg-zinc-100"></div>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-[1.1fr,0.9fr]">
            <div class="rounded-[1.8rem] border border-zinc-200/90 bg-zinc-50/70 p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]">
                <div class="h-4 w-24 rounded-full bg-zinc-100"></div>
                <div class="mt-4 h-9 w-52 rounded-full bg-zinc-100"></div>
                <div class="mt-3 h-4 w-64 rounded-full bg-zinc-100"></div>
                <div class="mt-8 h-[220px] rounded-[1.6rem] border border-zinc-100 bg-white px-5 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.65)]">
                    <div class="flex h-full items-end gap-3">
                        @foreach ([45, 72, 58, 86, 64, 92] as $bar)
                            <div class="flex flex-1 flex-col justify-end gap-2">
                                <div class="w-full rounded-t-xl bg-zinc-100" style="height: {{ $bar }}%"></div>
                                <div class="mx-auto h-3 w-8 rounded-full bg-zinc-100"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-px overflow-hidden rounded-[1.8rem] border border-zinc-200/90 bg-zinc-200/70 sm:grid-cols-2">
                @foreach (range(1, 4) as $item)
                    <div class="bg-white p-5">
                        <div class="h-3 w-24 rounded-full bg-zinc-100"></div>
                        <div class="mt-5 h-14 w-20 rounded-[1rem] bg-zinc-100"></div>
                        <div class="mt-4 h-4 w-32 rounded-full bg-zinc-100"></div>
                        <div class="mt-2 h-4 w-24 rounded-full bg-zinc-100"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="overflow-hidden rounded-[2rem] border border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
            <div class="border-b border-zinc-200 px-5 py-4 lg:px-6">
                <div class="h-3 w-36 rounded-full bg-zinc-100"></div>
                <div class="mt-3 h-7 w-56 rounded-full bg-zinc-100"></div>
            </div>
            <div class="space-y-4 p-5">
                @foreach ([82, 38] as $bar)
                    <div class="rounded-[1.6rem] border border-zinc-200/90 bg-zinc-50/70 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)]">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-2">
                                <div class="h-5 w-52 rounded-full bg-zinc-100"></div>
                                <div class="h-4 w-36 rounded-full bg-zinc-100"></div>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-zinc-100"></div>
                        </div>
                        <div class="mt-4 h-[14px] rounded-full border border-zinc-100 bg-white px-[5px] py-[4px]">
                            <div class="h-full rounded-full bg-zinc-100" style="width: {{ $bar }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-2 overflow-hidden rounded-[2rem] border border-zinc-200/90 bg-white shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
            <div class="border-b border-zinc-200 px-5 py-4 lg:px-6">
                <div class="h-3 w-36 rounded-full bg-zinc-100"></div>
                <div class="mt-3 h-7 w-56 rounded-full bg-zinc-100"></div>
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-2 md:grid-cols-3">
                @foreach (range(1, 6) as $tile)
                    <div class="rounded-[1.55rem] border border-zinc-200/90 bg-white p-5 shadow-[0_8px_18px_rgba(15,23,42,0.04)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="h-3 w-20 rounded-full bg-zinc-100"></div>
                            <div class="h-8 w-12 rounded-full bg-zinc-100"></div>
                        </div>
                        <div class="mt-5 h-7 w-28 rounded-full bg-zinc-100"></div>
                        <div class="mt-4 space-y-2">
                            <div class="h-4 w-full rounded-full bg-zinc-100"></div>
                            <div class="h-4 w-4/5 rounded-full bg-zinc-100"></div>
                            <div class="h-4 w-3/5 rounded-full bg-zinc-100"></div>
                        </div>
                        <div class="mt-6 h-9 w-28 rounded-full bg-zinc-100"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
