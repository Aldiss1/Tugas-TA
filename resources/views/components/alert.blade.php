@props(['variant' => 'success'])
@php
    $map = [
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'error' => 'bg-rose-50 text-rose-800 ring-rose-200',
        'info' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-200',
    ];
@endphp
@php $kelas = $map[$variant] ?? $map['info']; @endphp
<div {{ $attributes->merge(['class' => "rounded-xl px-4 py-3 text-sm ring-1 $kelas"]) }}>
    {{ $slot }}
</div>
