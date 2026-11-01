@extends('layouts.portal')

@section('title', 'Status Pendaftaran')

@section('content')
    @if (! $p)
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-10 text-center">
            <span class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-brand-50 text-brand-600 mb-4">
                <x-icon-document-text class="h-7 w-7" />
            </span>
            <h2 class="text-xl font-bold text-slate-900">Belum Ada Pendaftaran</h2>
            <p class="mt-2 text-sm text-slate-500">Isi formulir pendaftaran terlebih dahulu.</p>
            <a href="{{ route('pendaftaran') }}"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition shadow-sm">
                Mulai Pendaftaran
            </a>
        </div>
    @else
        {{-- Kartu utama --}}
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <div class="text-sm text-slate-500">Halo, {{ auth()->user()->name }}</div>
                    <div class="mt-1 font-mono text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                        {{ $p->nomor_pendaftaran ?? '(belum terkirim)' }}
                    </div>
                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        <x-status-badge :status="$p->status" class="!text-sm !px-3 !py-1" />
                        <span class="text-xs text-slate-400">
                            @forelse ($p->pilihan()->with('jurusan')->orderBy('prioritas')->get() as $pl)
                                P{{ $pl->prioritas }}: {{ $pl->jurusan->nama }} @if(!$loop->last) &bull; @endif
                            @empty
                                Jurusan belum dipilih.
                            @endforelse
                        </span>
                    </div>
                </div>
                @if ($bolehRevisi && $p->status->value === 'gugur')
                    <form method="POST" action="{{ url('/pendaftaran/revisi') }}">
                        @csrf
                        <input type="hidden" name="pendaftar_id" value="{{ $p->id }}">
                        <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition shadow-sm">
                            Kirim Ulang Pendaftaran
                        </button>
                    </form>
                @elseif ($bolehRevisi)
                    <a href="{{ route('pendaftaran') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white ring-1 ring-inset ring-slate-300 hover:bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700 transition">
                        Lanjutkan / Perbarui Draf
                    </a>
                @endif
            </div>
            @if ($p->alasan_gugur)
                <div class="mt-4">
                    <x-alert variant="error"><strong>Alasan gugur:</strong> {{ $p->alasan_gugur }}</x-alert>
                </div>
            @endif
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Kelengkapan dokumen --}}
            <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-6">
                <h3 class="font-bold text-slate-900 mb-4">Kelengkapan Dokumen</h3>
                @php $mapDok = $p->dokumen()->get()->keyBy(fn ($d) => $d->jenis->value); @endphp
                <ul class="divide-y divide-slate-100">
                    @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                        @php $dok = $mapDok[$jenis->value] ?? null; $ada = $dok && $dok->diunggah_at; @endphp
                        <li class="flex items-center justify-between py-2.5">
                            <span class="text-sm text-slate-700">{{ $jenis->label() }}</span>
                            @if ($ada)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                    <x-icon-check class="h-3.5 w-3.5" /> Tersimpan
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">Belum</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Info --}}
            <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-6">
                <h3 class="font-bold text-slate-900 mb-4">Info</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Nama</dt>
                        <dd class="font-medium text-slate-900 text-right">{{ $p->nama_lengkap }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">NISN</dt>
                        <dd class="font-mono font-medium text-slate-900">{{ $p->nisn }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Asal Sekolah</dt>
                        <dd class="font-medium text-slate-900 text-right">{{ $p->asal_sekolah }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Dikirim</dt>
                        <dd class="font-medium text-slate-900">{{ $p->dikirim_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            @if ($p->hasil && $sudahTerbit)
                {{-- Hasil --}}
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm ring-1 {{ $p->hasil->status_akhir === 'diterima' ? 'ring-emerald-200' : 'ring-slate-100' }} p-6">
                    <h3 class="font-bold text-slate-900 mb-3">Hasil Seleksi</h3>
                    <p class="text-sm text-slate-600">
                        Status akhir:
                        <strong class="text-base {{ $p->hasil->status_akhir === 'diterima' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ ucfirst(str_replace('_', ' ', $p->hasil->status_akhir)) }}
                        </strong>
                    </p>
                    @if ($p->hasil->jurusan)
                        <p class="mt-1 text-sm text-slate-600">
                            Jurusan diterima: <strong>{{ $p->hasil->jurusan->nama }}</strong> (peringkat {{ $p->hasil->perankingan }})
                        </p>
                    @endif
                    @if ($p->hasil->status_akhir === 'diterima')
                        <a href="{{ url('/surat/'.$p->nomor_pendaftaran) }}" target="_blank"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition shadow-sm">
                            <x-icon-printer class="h-4 w-4" />
                            Cetak Surat Keterangan Diterima
                        </a>
                    @endif
                </div>
            @elseif ($p->status->value === 'lulus_verifikasi' && ! $sudahTerbit)
                <div class="lg:col-span-2">
                    <x-alert variant="info">Menunggu pengumuman — hasil seleksi akan ditampilkan di halaman ini setelah pengumuman diterbitkan.</x-alert>
                </div>
            @endif
        </div>
    @endif
@endsection
