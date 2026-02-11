@props(['headers','divide' => true])

<div class="relative w-full overflow-clip">
    <table {{ $attributes->merge(['class' => '-my-1 w-full caption-bottom border-separate border-spacing-y-1 text-xs max-md:flex max-md:w-full max-md:flex-col max-md:py-2']) }}>
        <thead class="z-20 rounded-md backdrop-blur-md md:sticky md:top-0 before:absolute before:inset-x-0 before:-top-[2px] before:-z-10 before:h-[5px] before:bg-background max-md:hidden">
        <tr class="group/row whitespace-nowrap md:[&amp;_td:first-child]:border-l md:[&amp;_td:last-child]:border-r md:[&amp;_td]:border-y max-md:flex max-md:w-full max-md:flex-col bg-neutral-800/50 [&amp;:hover_td]:md:!bg-neutral-800 [&amp;_td]:md:border-neutral-700/40 max-md:overflow-hidden max-md:rounded-lg max-md:border content-visibility-auto">
            @foreach ($headers as $header)
                @if($header != 'action')
                    <th class="h-10 px-2 text-left align-middle font-sans font-medium uppercase text-muted-foreground first:rounded-l-md first:pl-5 last:rounded-r-md last:pr-5 md:px-3.5 [&amp;:has([role=checkbox])]:pr-0 first:border-l last:border-r border-y border-neutral-700/40 max-md:hidden" colspan="1">
                        <span class="transition-transform duration-100 max-md:translate-x-0">{{ $header }}</span>
                    </th>
                @else
                    <th class="h-10 px-2 text-left align-middle font-sans font-medium uppercase text-muted-foreground first:rounded-l-md first:pl-5 last:rounded-r-md last:pr-5 md:px-3.5 [&amp;:has([role=checkbox])]:pr-0 border-y first:border-l last:border-r"></th>
                @endif
            @endforeach
        </tr>
        </thead>

      <tbody class="pb-4 md:pb-12 content-visibility-auto contain-intrinsic-size-auto max-md:flex max-md:w-full max-md:flex-col max-md:gap-4">
        {{ $slot }}
        </tbody>
    </table>
</div>
