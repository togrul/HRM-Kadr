@props(['rows' => 4])

<x-ui.textarea :rows="$rows" {{ $attributes }}>{{ $slot }}</x-ui.textarea>
