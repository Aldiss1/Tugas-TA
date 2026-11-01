@extends('layouts.admin')

@section('title', 'Data Pendaftar')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Data Pendaftar</h2>
        <a href="{{ route('admin.pendaftar.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition">
            <x-icon-plus class="h-4 w-4" /> Input Manual
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-4">
        <form method="GET" action="{{ url('/admin/pendaftar') }}" class="grid gap-3 sm:grid-cols-4">
            <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua status</option>
                @foreach ($status as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <select name="jurusan" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua jurusan</option>
                @foreach ($jurusan as $j)
                    <option value="{{ $j->id }}" @selected((string) request('jurusan') === (string) $j->id)>{{ $j->kode }} — {{ $j->nama }}</option>
                @endforeach
            </select>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama/NISN/no. pendaftaran"
                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-white ring-1 ring-inset ring-slate-300 hover:bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition">
                <x-icon-magnifying-glass class="h-4 w-4" /> Filter
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">Pendaftar</th>
                        <th class="px-5 py-3">NISN</th>
                        <th class="px-5 py-3">Jurusan</th>
                        <th class="px-5 py-3">Dokumen</th>
                        <th class="px-5 py-3">Sumber</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendaftar as $p)
                        @php
                            $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value);
                            $pl1 = $p->pilihan->firstWhere('prioritas', 1);
                            $pl2 = $p->pilihan->firstWhere('prioritas', 2);
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-full bg-brand-100 text-brand-700 text-xs font-bold">
                                        {{ strtoupper(mb_substr($p->nama_lengkap, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900 truncate">{{ $p->nama_lengkap }}</div>
                                        <div class="text-xs font-mono text-slate-400">{{ $p->nomor_pendaftaran ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $p->nisn }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $pl1?->jurusan?->kode ?? '—' }}</span>
                                @if ($pl2)
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-slate-200 ml-1">{{ $pl2->jurusan->kode }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1">
                                    @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                        @php $d = $mapDok[$jenis->value] ?? null; @endphp
                                        <span class="h-2 w-2 rounded-full {{ ($d && $d->diunggah_at) ? 'bg-emerald-500' : 'bg-slate-200' }}" title="{{ $jenis->label() }}"></span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $p->sumber }}</td>
                            <td class="px-5 py-3"><x-status-badge :status="$p->status" /></td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ url('/admin/pendaftar/'.$p->id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pendaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pendaftar->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap gap-1.5">
                @if ($pendaftar->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50">«</span>
                @else
                    <a href="{{ $pendaftar->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">«</a>
                @endif
                @for ($i = max(1, $pendaftar->currentPage() - 2); $i <= min($pendaftar->lastPage(), $pendaftar->currentPage() + 2); $i++)
                    @if ($i === $pendaftar->currentPage())
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-brand-600">{{ $i }}</span>
                    @else
                        <a href="{{ $pendaftar->url($i) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">{{ $i }}</a>
                    @endif
                @endfor
                @if ($pendaftar->onLastPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50">»</span>
                @else
                    <a href="{{ $pendaftar->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">»</a>
                @endif
            </div>
        @endif
    </div>
@endsection
