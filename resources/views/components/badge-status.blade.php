@props(['status'])
@php
    $map = [
        \App\Enums\StatusPendaftar::DRAF->value => 'bg-slate-100 text-slate-700 ring-slate-200',
        \App\Enums\StatusPendaftar::MENUNGGU_VERIFIKASI->value => 'bg-amber-50 text-amber-800 ring-amber-200',
        \App\Enums\StatusPendaftar::LULUS_VERIFIKASI->value => 'bg-sky-50 text-sky-800 ring-sky-200',
        \App\Enums\StatusPendaftar::GUGUR->value => 'bg-rose-50 text-rose-800 ring-rose-200',
        \App\Enums\StatusPendaftar::DITERIMA->value => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        \App\Enums\StatusPendaftar::CADANGAN->value => 'bg-white text-amber-700 ring-2 ring-amber-300',
        \App\Enums\StatusPendaftar::TIDAK_DITERIMA->value => 'bg-slate-100 text-slate-500 ring-slate-200',
    ];
    $kelas = $map[$status->value] ?? $map[\App\Enums\StatusPendaftar::DRAF->value];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {$kelas}"]) }}>
    {{ $status->label() }}
</span>
