@props([
  'text' => '',
  'query' => '',
])

@php
    // Every hit of the tree search goes into <mark>; each piece is escaped first. Matching uses
    // the same Azerbaijani-aware folding as the search itself (İ/i, I/ı).
    $text = (string) $text;
    $needle = \App\Modules\Staff\Livewire\Staffs::foldCase((string) $query);
    $html = '';

    if ($needle === '') {
        $html = e($text);
    } else {
        $haystack = \App\Modules\Staff\Livewire\Staffs::foldCase($text);
        $length = mb_strlen($needle);
        $offset = 0;
        while (($hit = mb_strpos($haystack, $needle, $offset)) !== false) {
            $html .= e(mb_substr($text, $offset, $hit - $offset))
                .'<mark class="rounded bg-amber-100 px-0.5 text-ink">'.e(mb_substr($text, $hit, $length)).'</mark>';
            $offset = $hit + $length;
        }
        $html .= e(mb_substr($text, $offset));
    }
@endphp
{!! $html !!}
