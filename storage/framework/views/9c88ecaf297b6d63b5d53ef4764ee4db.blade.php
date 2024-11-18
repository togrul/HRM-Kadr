<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
@props(['color','hover'])
<x-icons.recover :color="$color" :hover="$hover" >

{{ $slot ?? "" }}
</x-icons.recover>