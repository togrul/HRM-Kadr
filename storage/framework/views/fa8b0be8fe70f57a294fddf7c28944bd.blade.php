<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
@props(['size','color'])
<x-icons.check-simple-icon :size="$size" :color="$color" >

{{ $slot ?? "" }}
</x-icons.check-simple-icon>