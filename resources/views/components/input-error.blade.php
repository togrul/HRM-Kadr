@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-2 space-y-2']) }}>
        @foreach ((array) $messages as $message)
            <li class="rounded-2xl bg-rose-500 px-3.5 py-1.5 text-xs font-semibold leading-5 text-white shadow-sm shadow-rose-500/10 break-words">
                {{ $message }}
            </li>
        @endforeach
    </ul>
@endif
