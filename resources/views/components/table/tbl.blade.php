@props([
    'headers',
    'divide' => true,
    'title' => null,
])

<div class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100/80 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
    <div class="px-4 py-2.5">
        @if (filled($title))
            <h3 class="text-sm uppercase font-mono font-medium text-slate-600 tracking-tight">
                {{ $title }}
            </h3>
        @endif
    </div>

    <div class="px-1 pb-1">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <table {{ $attributes->merge(['class' => 'min-w-full w-full p-[5px] pb-0 border-separate border-spacing-0 bg-white text-sm']) }}>
            <thead class="bg-transparent">
                <tr class="align-middle font-mono text-xs uppercase">
                    @foreach ($headers as $header)
                        @if ($header != 'action')
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-left text-[14px] font-medium tracking-normal text-slate-500 whitespace-nowrap bg-zinc-100/70 border-y border-zinc-200 first:rounded-l-xl first:border-l last:rounded-r-xl last:border-r"
                            >
                                {{ $header }}
                            </th>
                        @else
                            <th
                                scope="col"
                                class="relative px-4 py-2.5 bg-zinc-100/70 border-y border-zinc-200 first:rounded-l-xl first:border-l last:rounded-r-xl last:border-r"
                            >
                                <span class="sr-only">{{ __('Edit') }}</span>
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <tbody @class([
                'bg-white [&_tr]:transition-colors [&_tr:hover]:bg-zinc-50/60',
                '[&_tr>td]:border-b [&_tr>td]:border-zinc-200 [&_tr:last-child>td]:border-b-0' => $divide,
            ])>
                {{ $slot }}
            </tbody>
        </table>
        </div>
    </div>
</div>
