@extends('layouts.admin')

@section('title', 'Detail Pendaftar')

@section('content')
    <div class="flex items-center gap-3">
        <a href="{{ url('/admin/pendaftar') }}"
            class="inline-flex items-center justify-center h-9 w-9 rounded-lg ring-1 ring-inset ring-slate-300 text-slate-500 hover:bg-slate-100 transition">
            <x-icon-arrow-left class="h-4 w-4" />
        </a>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-brand-100 text-brand-700 text-lg font-bold">
                {{ strtoupper(mb_substr($p->nama_lengkap, 0, 1)) }}
            </span>
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $p->nama_lengkap }}</h2>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span class="text-sm font-mono text-slate-500">{{ $p->nomor_pendaftaran ?? '—' }}</span>
                    <x-status-badge :status="$p->status" />
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200 uppercase">{{ $p->sumber }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($p->alasan_gugur)
        <x-alert variant="error" class="mt-4"><strong>Alasan gugur:</strong> {{ $p->alasan_gugur }}</x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 mt-6">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
            <h3 class="font-bold text-slate-900 mb-4">Data Pendaftar</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500 text-xs uppercase tracking-wide">NISN</dt><dd class="font-mono font-medium text-slate-900 mt-0.5">{{ $p->nisn }}</dd></div>
                <div><dt class="text-slate-500 text-xs uppercase tracking-wide">NIK</dt><dd class="font-mono font-medium text-slate-900 mt-0.5">{{ $p->nik }}</dd></div>
                <div><dt class="text-slate-500 text-xs uppercase tracking-wide">TTL</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->tempat_lahir }}, {{ $p->tanggal_lahir->format('d M Y') }} ({{ $p->jenis_kelamin }})</dd></div>
                <div><dt class="text-slate-500 text-xs uppercase tracking-wide">Agama</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->agama }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500 text-xs uppercase tracking-wide">Asal Sekolah</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->asal_sekolah }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500 text-xs uppercase tracking-wide">Alamat</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->alamat }}</dd></div>
                <div><dt class="text-slate-500 text-xs uppercase tracking-wide">Orang Tua / Wali</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->nama_orang_tua }} ({{ $p->pekerjaan_orang_tua }})</dd></div>
                <div><dt class="text-slate-500 text-xs uppercase tracking-wide">Telepon</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->telepon_orang_tua }}</dd></div>
                <div class="col-span-2">
                    <dt class="text-slate-500 text-xs uppercase tracking-wide">Jurusan Pilihan</dt>
                    <dd class="mt-1 flex flex-wrap gap-1.5">
                        @forelse ($p->pilihan as $pl)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">P{{ $pl->prioritas }}: {{ $pl->jurusan->nama }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
                <div class="col-span-2"><dt class="text-slate-500 text-xs uppercase tracking-wide">Dikirim</dt><dd class="font-medium text-slate-900 mt-0.5">{{ $p->dikirim_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
                <h3 class="font-bold text-slate-900 mb-4">Kelengkapan Dokumen</h3>
                @php $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value); @endphp
                <ul class="divide-y divide-slate-100">
                    @foreach ($jenisDokumen as $jenis)
                        @php $d = $mapDok[$jenis->value] ?? null; $ada = $d && $d->diunggah_at; @endphp
                        <li class="flex items-center justify-between py-2.5">
                            <span class="text-sm text-slate-700">{{ $jenis->label() }}</span>
                            <span class="flex items-center gap-2">
                                @if ($ada)
                                    <span class="text-xs text-slate-400">{{ round($d->ukuran_bytes / 1024, 1) }} KB</span>
                                    <a href="{{ url('/admin/pendaftar/'.$p->id.'/dokumen/'.$jenis->value) }}" target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50 transition">
                                        <x-icon-magnifying-glass class="h-3 w-3" /> Preview
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Belum</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
                <h3 class="font-bold text-slate-900 mb-4">Nilai</h3>
                @if ($p->nilai)
                    <div class="grid grid-cols-4 gap-3 text-center">
                        @foreach ([['Rapor', $p->nilai->nilai_rapor], ['Prestasi', $p->nilai->nilai_prestasi], ['Kunjungan', $p->nilai->nilai_kunjungan], ['Total', $p->nilai->total]] as [$label, $val])
                            <div class="rounded-xl bg-slate-50 p-3">
                                <div class="text-xs text-slate-500">{{ $label }}</div>
                                <div class="text-xl font-extrabold text-slate-900 mt-1">{{ $val }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if ($p->hasil)
                        <p class="mt-4 text-sm text-slate-600">
                            Hasil: <strong class="{{ $p->hasil->status_akhir === 'diterima' ? 'text-emerald-600' : 'text-rose-600' }}">{{ ucfirst(str_replace('_', ' ', $p->hasil->status_akhir)) }}</strong>
                            @if ($p->hasil->jurusan) — {{ $p->hasil->jurusan->nama }}, peringkat {{ $p->hasil->perankingan }} @endif
                        </p>
                    @endif
                @else
                    <p class="text-sm text-slate-500 mb-0">Belum ada nilai. <a href="{{ url('/admin/nilai') }}" class="text-brand-700 font-semibold">Input Nilai</a></p>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6 mt-6">
        <h3 class="font-bold text-slate-900 mb-4">Riwayat</h3>
        @forelse ($riwayat as $r)
            <div class="flex items-start gap-3 py-2.5 border-b border-slate-50 last:border-0">
                <span class="mt-1 h-2 w-2 rounded-full bg-brand-400 shrink-0"></span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm">
                        <span class="font-semibold text-slate-900">{{ $r->aksi }}</span>
                        <span class="text-slate-400 text-xs">— {{ $r->created_at?->format('d M Y H:i') }} oleh {{ $r->pengguna?->name ?? 'Sistem' }}</span>
                    </div>
                    @if ($r->detail)
                        <div class="text-xs font-mono text-slate-500 mt-0.5 break-all">{{ json_encode($r->detail, JSON_UNESCAPED_UNICODE) }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500 mb-0">Belum ada aktivitas tercatat.</p>
        @endforelse
    </div>
@endsection
