<div class="px-4 py-3 space-y-3">
    @foreach(range(1, 10) as $row)
        <div class="flex items-center gap-2 animate-pulse">
            <span class="w-1.5 h-1.5 rounded-full bg-neutral-300"></span>
            <span class="h-3 rounded bg-neutral-200" style="width: {{ 45 + (($row % 4) * 10) }}%"></span>
        </div>
    @endforeach
</div>

