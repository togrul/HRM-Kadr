@props(['title' => ''])

<button
    {{ $attributes->merge([
        'class' => 'flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300',
        'type' => 'button',
        'title' => $title,
    ]) }}>
    {{ $slot }}
</button>
