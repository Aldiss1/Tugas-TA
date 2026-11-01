@extends('layouts.admin')

@section('title', 'Jurusan & Kuota')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Jurusan & Kuota</h2>
        <a href="{{ route('admin.jurusan.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition">
            <x-icon-plus class="h-4 w-4" /> Tambah Jurusan
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Program Keahlian</th>
                        <th class="px-5 py-3 text-center">Rombel</th>
                        <th class="px-5 py-3 text-center">Kapasitas</th>
                        <th class="px-5 py-3 text-center">Kuota</th>
                        <th class="px-5 py-3 text-center">Cadangan</th>
                        <th class="px-5 py-3 text-center">Terisi</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($jurusan as $j)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold text-brand-700 ring-1 ring-brand-200">{{ $j->kode }}</span>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $j->nama }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $j->rombel }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $j->kapasitas() }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $j->kuota->kuota ?? 0 }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $j->kuota->cadangan ?? 0 }}</td>
                            <td class="px-5 py-3 text-center font-semibold text-slate-900">{{ $j->kuota->terisi ?? 0 }}</td>
                            <td class="px-5 py-3 text-center">
                                @if ($j->aktif)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.jurusan.edit', $j) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">
                                    <x-icon-magnifying-glass class="h-3.5 w-3.5" /> Edit
                                </a>
                                <form method="POST" action="{{ route('admin.jurusan.destroy', $j) }}" class="inline"
                                    onsubmit="return confirm('Hapus jurusan {{ $j->kode }}? Jika sudah ada pendaftar, jurusan hanya akan dinonaktifkan.');">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50 transition">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
