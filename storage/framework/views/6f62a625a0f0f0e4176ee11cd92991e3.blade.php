<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
@props(['size','color','hover'])
<x-icons.book-icon :size="$size" :color="$color" :hover="$hover" >

{{ $slot ?? "" }}
</x-icons.book-icon>