@props(['status'])
@php
    $warna = match ($status) {
        \App\Enums\StatusPendaftar::DRAF => 'secondary',
        \App\Enums\StatusPendaftar::MENUNGGU_VERIFIKASI => 'warning',
        \App\Enums\StatusPendaftar::LULUS_VERIFIKASI => 'info',
        \App\Enums\StatusPendaftar::GUGUR => 'danger',
        \App\Enums\StatusPendaftar::DITERIMA => 'success',
        \App\Enums\StatusPendaftar::CADANGAN => 'warning',
        \App\Enums\StatusPendaftar::TIDAK_DITERIMA => 'secondary',
    };
    $classes = 'badge bg-'.$warna.($attributes->has('class') ? ' '.$attributes['class'] : '');
@endphp
<span class="{{ $classes }}">{{ $status->label() }}</span>
