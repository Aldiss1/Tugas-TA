@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Laporan</h2>
        <div class="flex items-center gap-2 flex-wrap">
            <select id="filter-status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua status</option>
                @foreach ($status as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <a id="link-csv" href="{{ url('/admin/laporan/export/csv'.(request('status') ? '?status='.request('status') : '')) }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-white ring-1 ring-inset ring-slate-300 hover:bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition">
                <x-icon-document-text class="h-4 w-4" /> Ekspor CSV
            </a>
            <a id="link-xlsx" href="{{ url('/admin/laporan/export/xlsx'.(request('status') ? '?status='.request('status') : '')) }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition">
                <x-icon-document-text class="h-4 w-4" /> Ekspor Excel
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mt-6">
        @foreach ([
            ['Pendaftar', $total['pendaftar'], 'brand'],
            ['Diterima', $total['diterima'], 'emerald'],
            ['Cadangan', $total['cadangan'], 'amber'],
            ['Tidak Diterima', $total['tidak_diterima'], 'slate'],
        ] as [$label, $n, $warna])
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
                <div class="text-xs font-bold uppercase tracking-wider text-{{ $warna }}-600">{{ $label }}</div>
                <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $n }}</div>
            </div>
        @endforeach
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
                        <th class="px-5 py-3 text-center">Pendaftar</th>
                        <th class="px-5 py-3 text-center">Diterima</th>
                        <th class="px-5 py-3 text-center">Cadangan</th>
                        <th class="px-5 py-3 text-center">Kuota</th>
                        <th class="px-5 py-3">Sisa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rekap as $kode => $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold text-brand-700 ring-1 ring-brand-200 mr-2">{{ $kode }}</span>
                                <span class="font-medium text-slate-900">{{ $r['nama'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $r['pendaftar'] }}</td>
                            <td class="px-5 py-3 text-center font-semibold text-emerald-600">{{ $r['terima'] }}</td>
                            <td class="px-5 py-3 text-center text-amber-600">{{ $r['cadangan'] }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $r['kuota'] }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden flex-1">
                                        <div class="h-full bg-brand-500 rounded-full" style="width: {{ $r['kuota'] ? (int) round($r['terima'] / $r['kuota'] * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500 w-16 text-right">Sisa: {{ $r['sisa'] }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('filter-status').addEventListener('change', function () {
            var s = this.value;
            var base = s ? '?status=' + s : '';
            document.getElementById('link-csv').href = '{{ url('/admin/laporan/export/csv') }}' + base;
            document.getElementById('link-xlsx').href = '{{ url('/admin/laporan/export/xlsx') }}' + base;
        });
    </script>
@endsection
