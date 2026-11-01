@extends('layouts.admin')

@section('title', 'Pengumuman')

@section('content')
    <h2 class="text-2xl font-bold tracking-tight text-slate-900 mb-5">Pengumuman PSB {{ config('psb.tahun_ajaran') }}</h2>

    @if ($terbit)
        <x-alert variant="success" class="flex items-center gap-2">
            <x-icon-bell class="h-5 w-5 shrink-0" />
            <span><strong>Pengumuman TERBIT</strong> pada {{ $waktu?->format('d M Y H:i') }}. Nilai dan ranking terkunci — calon siswa sudah dapat melihat hasil.</span>
        </x-alert>
    @else
        <x-alert variant="warning" class="flex items-center gap-2">
            <x-icon-exclamation class="h-5 w-5 shrink-0" />
            <span>Pengumuman belum terbit. Pastikan proses ranking sudah selesai dan ditinjau.</span>
        </x-alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-3 mt-6">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-emerald-100 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-emerald-600">DITERIMA</div>
            <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $stat['diterima'] }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-amber-100 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-amber-600">CADANGAN</div>
            <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $stat['cadangan'] }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">TIDAK DITERIMA</div>
            <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $stat['tidak_diterima'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden mt-6">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Rekap per Jurusan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">Jurusan</th>
                        <th class="px-5 py-3 text-center">Kuota</th>
                        <th class="px-5 py-3 text-center">Terisi</th>
                        <th class="px-5 py-3 text-center">Cadangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($jurusan as $j)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold text-brand-700 ring-1 ring-brand-200 mr-2">{{ $j->kode }}</span>
                                <span class="font-medium text-slate-900">{{ $j->nama }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $j->kuota->kuota ?? 0 }}</td>
                            <td class="px-5 py-3 text-center font-semibold text-slate-900">{{ $j->kuota->terisi ?? 0 }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $j->kuota->cadangan ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if (! $terbit)
        <div class="mt-6">
            <form method="POST" action="{{ url('/admin/pengumuman/terbit') }}"
                onsubmit="return confirm('Terbitkan pengumuman? Tindakan ini mengunci semua data seleksi.');">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-6 py-3 text-sm font-bold text-white shadow transition">
                    <x-icon-bell class="h-4 w-4" /> Terbitkan Pengumuman
                </button>
            </form>
        </div>
    @endif
@endsection
