@extends('layouts.public')

@section('title', 'Cek Status')

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="text-center mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Cek Status Pendaftaran</h1>
            <p class="mt-2 text-sm text-slate-500">
                Masukkan <strong>nomor pendaftaran</strong> dan <strong>tanggal lahir</strong> Anda.
                Halaman ini juga berlaku untuk pendaftar yang datanya diinput manual oleh sekolah.
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
            <form method="POST" action="{{ route('cek-status') }}">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nomor" class="block text-sm font-medium text-slate-700 mb-1">Nomor Pendaftaran</label>
                        <input id="nomor" type="text" name="nomor" value="{{ old('nomor') }}" placeholder="PSB-2026-000001"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                        @error('nomor') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="tgl_lahir" class="block text-sm font-medium text-slate-700 mb-1">Tanggal Lahir</label>
                        <input id="tgl_lahir" type="date" name="tgl_lahir" value="{{ old('tgl_lahir') }}"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                    </div>
                </div>
                <button type="submit" class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition">
                    <x-icon-magnifying-glass class="h-4 w-4" />
                    Cek Status
                </button>
            </form>
        </div>

        @if (isset($pendaftar))
            <div class="mt-6 bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $pendaftar->nama_lengkap }}</h2>
                        <div class="text-sm font-mono text-slate-500">{{ $pendaftar->nomor_pendaftaran }}</div>
                    </div>
                    <x-status-badge :status="$pendaftar->status" />
                </div>

                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Jurusan Pilihan</dt>
                        <dd class="flex flex-wrap gap-1.5 justify-end">
                            @forelse ($pendaftar->pilihan()->with('jurusan')->orderBy('prioritas')->get() as $pl)
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                    P{{ $pl->prioritas }}: {{ $pl->jurusan->nama }}
                                </span>
                            @empty
                                —
                            @endforelse
                        </dd>
                    </div>
                </dl>

                @if ($pendaftar->alasan_gugur)
                    <div class="mt-4">
                        <x-alert variant="error"><strong>Alasan:</strong> {{ $pendaftar->alasan_gugur }}</x-alert>
                    </div>
                @endif

                @if ($pendaftar->hasil && $sudahTerbit)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <h3 class="text-sm font-semibold text-slate-900 mb-1">Hasil Seleksi</h3>
                        <p class="text-sm">
                            Status akhir:
                            <strong class="{{ $pendaftar->hasil->status_akhir === 'diterima' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ ucfirst(str_replace('_', ' ', $pendaftar->hasil->status_akhir)) }}
                            </strong>
                            @if ($pendaftar->hasil->jurusan)
                                — {{ $pendaftar->hasil->jurusan->nama }} (peringkat {{ $pendaftar->hasil->perankingan }})
                            @endif
                        </p>
                    </div>
                @elseif ($pendaftar->status->value === 'lulus_verifikasi' && ! $sudahTerbit)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <x-alert variant="info">Menunggu pengumuman — hasil seleksi akan ditampilkan setelah pengumuman diterbitkan.</x-alert>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
