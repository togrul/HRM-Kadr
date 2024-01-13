@props([
    'name',
    'specialty',
    'admission_year',
    'graduated_year'
])

<div style="padding: 3px;">
    <span>{{ $name }}</span>,
    <span>{{ $specialty }}</span> -
    <span>{{ \Carbon\Carbon::parse($admission_year)->format('d.m.Y') }}</span> -
    <span>{{ \Carbon\Carbon::parse($graduated_year)->format('d.m.Y') }}</span>
</div>
