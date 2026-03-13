<div class="flex flex-col w-full p-10 px-0 mx-auto my-3 mb-4 space-y-6 bg-white">
    <div class="space-y-3">
        <div class="h-6 w-48 rounded-lg bg-slate-100 animate-pulse"></div>
        <div class="h-4 w-36 rounded-md bg-slate-100/80 animate-pulse"></div>
    </div>

    <div class="grid grid-cols-3 gap-2">
        @for ($i = 0; $i < 3; $i++)
            <div class="h-11 rounded-lg bg-slate-100 animate-pulse"></div>
        @endfor
    </div>

    <div class="grid grid-cols-4 gap-2">
        @for ($i = 0; $i < 4; $i++)
            <div class="h-11 rounded-lg bg-slate-100 animate-pulse"></div>
        @endfor
    </div>

    <div class="grid grid-cols-2 gap-2">
        <div class="h-28 rounded-lg bg-slate-100 animate-pulse"></div>
        <div class="h-28 rounded-lg bg-slate-100 animate-pulse"></div>
    </div>

    <div class="flex justify-end">
        <div class="h-10 w-32 rounded-lg bg-slate-200 animate-pulse"></div>
    </div>
</div>
