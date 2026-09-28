@props([
  'text' => '',
  'query' => '',
])

@php
    // Every case-insensitive hit of the tree search goes into <mark>; each piece is escaped first.
    $html = e((string) $text);

    if ($query !== '') {
        $pieces = preg_split('/('.preg_quote($query, '/').')/iu', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [(string) $text];
        $html = '';
        foreach ($pieces as $i => $piece) {
            $html .= $i % 2 === 1
                ? '<mark class="rounded bg-amber-100 px-0.5 text-ink">'.e($piece).'</mark>'
                : e($piece);
        }
    }
@endphp
{!! $html !!}
