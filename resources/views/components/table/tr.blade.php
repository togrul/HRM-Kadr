@props([
    'dark' => false
])

<tr class="group/row whitespace-nowrap md:[&_td:first-child]:border-l md:[&_td:last-child]:border-r md:[&_td]:border-y max-md:flex max-md:w-full max-md:flex-col bg-neutral-800/50 [&:hover_td]:md:!bg-neutral-800 [&_td]:md:border-neutral-700/40 max-md:overflow-hidden max-md:rounded-lg max-md:border cursor-pointer content-visibility-auto">
    {{ $slot }}
</tr>
