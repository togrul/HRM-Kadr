@props([
    'headers',
    'divide' => true,
    'title' => null,
    'bordered' => false,
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
                      @php
                        $isDay = is_numeric($header);
                      @endphp
                        @if ($header != 'action')
                            <th
                                scope="col"
                                @class([
                                  'text-left text-[14px] font-medium tracking-normal text-slate-500 whitespace-nowrap bg-zinc-100/70 border-y border-zinc-200 first:rounded-l-xl first:border-l last:rounded-r-xl last:border-r ',
                                  'stats-cell-header py-1 px-4' => $bordered,
                                  'py-2.5 px-4' => !$bordered,
                                  'w-10 min-w-10 max-w-10 text-center !px-0 !py-0' => $bordered && $isDay
                                ])
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
                '[&_tr>td]:border-r [&_tr>td]:border-zinc-200 [&_tr>td:last-child]:border-r-0 [&_tr>td.stats-cell]:border-r-0 [&_tr>td]:px-2 [&_tr>td]:py-2' => $bordered,
            ])>
                {{ $slot }}
            </tbody>
        </table>
        </div>
    </div>
</div>
