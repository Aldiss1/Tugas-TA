@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-500 to-sky-500 text-white">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="relative px-6 sm:px-12 py-14 sm:py-20">
            <span class="inline-flex items-center rounded-full bg-white/15 ring-1 ring-white/25 px-3 py-1 text-xs font-semibold tracking-wide">
                TAHUN PELAJARAN {{ strtoupper(config('psb.tahun_ajaran')) }}
            </span>
            <h1 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-tight">Penerimaan Siswa Baru {{ config('psb.tahun_ajaran') }}</h1>
            <p class="mt-3 max-w-2xl text-base sm:text-lg text-brand-50/90">
                {{ config('psb.nama_sekolah') }} — Akreditasi A &bull; 7 Program Keahlian
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl bg-white text-brand-700 font-bold text-sm sm:text-base shadow-lg hover:bg-brand-50 transition">Daftar Sekarang</a>
                <a href="{{ route('cek-status') }}" class="px-6 py-3 rounded-xl ring-1 ring-inset ring-white/60 text-white font-semibold text-sm sm:text-base hover:bg-white/10 transition">Cek Status</a>
            </div>
            <dl class="mt-10 grid grid-cols-3 gap-4 max-w-lg">
                <div>
                    <dt class="text-2xl sm:text-3xl font-extrabold">{{ $jurusan->count() }}</dt>
                    <dd class="text-xs sm:text-sm text-brand-50/80">Program Keahlian</dd>
                </div>
                <div>
                    <dt class="text-2xl sm:text-3xl font-extrabold">{{ $jurusan->sum(fn ($j) => $j->kuota->kuota ?? 0) }}</dt>
                    <dd class="text-xs sm:text-sm text-brand-50/80">Total Kuota</dd>
                </div>
                <div>
                    <dt class="text-2xl sm:text-3xl font-extrabold">5</dt>
                    <dd class="text-xs sm:text-sm text-brand-50/80">Tahap Seleksi</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Status tahap aktif --}}
    @if ($tahapAktif)
        <x-alert variant="success" class="flex items-center gap-2">
            <x-icon-clock class="h-5 w-5 shrink-0" />
            <span><strong>Tahap aktif:</strong> {{ $tahapAktif->label() }} — persiapkan dokumen Anda dan pantau halaman ini.</span>
        </x-alert>
    @else
        <x-alert variant="info" class="flex items-center gap-2">
            <x-icon-clock class="h-5 w-5 shrink-0" />
            <span>Tidak ada tahap aktif saat ini — silakan pantau jadwal di bawah.</span>
        </x-alert>
    @endif

    {{-- Program Keahlian --}}
    <section id="jurusan">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Program Keahlian</h2>
        <p class="mt-1 text-sm text-slate-500">Pilih jurusan sesuai minat — kuota diperbarui setiap ada pendaftar baru.</p>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($jurusan as $j)
                @php $terisi = $j->kuota->terisi ?? 0; $kuota = $j->kuota->kuota ?? 0; $pct = $kuota ? (int) round($terisi / $kuota * 100) : 0; @endphp
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-brand-50 text-brand-600">
                            <x-icon-academic-cap class="h-5 w-5" />
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $j->kode }}</span>
                    </div>
                    <h3 class="mt-3 font-semibold text-slate-900">{{ $j->nama }}</h3>
                    <p class="mt-1 text-sm text-slate-500 flex-1">{{ Str::limit($j->deskripsi, 90) }}</p>
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-slate-500 mb-1">
                            <span>Terisi {{ $terisi }}/{{ $kuota }}</span>
                            <span>Sisa: <strong class="text-slate-700">{{ $j->kuota->sisa() ?? 0 }}</strong></span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Alur pendaftaran --}}
    <section class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6 sm:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Alur Pendaftaran</h2>
        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['1', 'Daftar Akun', 'Buat akun dengan email dan NISN, langsung aktif.'],
                ['2', 'Isi Form & Dokumen', 'Lengkapi data diri, pilih jurusan, unggah 5 dokumen.'],
                ['3', 'Verifikasi & Seleksi', 'Admin memverifikasi dokumen dan memproses nilai seleksi.'],
                ['4', 'Pengumuman', 'Pantau status dan cetak surat keterangan diterima.'],
            ] as [$no, $judul, $isi])
                <div class="relative pl-12">
                    <span class="absolute left-0 top-0 inline-flex items-center justify-center h-9 w-9 rounded-full bg-gradient-to-br from-brand-600 to-sky-500 text-white text-sm font-bold shadow">{{ $no }}</span>
                    <h3 class="font-semibold text-slate-900">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $isi }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Kalender PSB --}}
    <section id="jadwal">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Kalender PSB</h2>
        <div class="mt-5 bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 divide-y divide-slate-100">
            @foreach ($statusJadwal as $s)
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="inline-flex items-center justify-center h-9 w-9 shrink-0 rounded-xl {{ $s['aktif'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                            <x-icon-calendar class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-900 truncate">{{ $s['label'] }}</div>
                            <div class="text-xs text-slate-500">{{ $s['mulai'] ?? '—' }} &mdash; {{ $s['selesai'] ?? '—' }}</div>
                        </div>
                    </div>
                    @if ($s['aktif'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 shrink-0">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Berlangsung
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500 ring-1 ring-slate-200 shrink-0">Tidak aktif</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">FAQ</h2>
        <div class="mt-5 space-y-3">
            @foreach ([
                'Apa saja dokumen yang harus diunggah?' => 'KTP, Kartu Keluarga, ijazah/surat kelulusan, rapor semester terakhir, dan foto 3x4 (PDF/JPG/PNG, maks. 2 MB).',
                'Bisa memilih berapa jurusan?' => 'Maksimal 2 jurusan: prioritas 1 (wajib) dan prioritas 2 (opsional).',
                'Bagaimana jika kuota prioritas 1 penuh?' => 'Sistem melanjutkan proses ke jurusan prioritas 2 sesuai sisa kuota.',
                'Kapan pengumuman?' => 'Mengikuti kalender PSB di atas. Pantau terus status Anda.',
            ] as $q => $a)
                <details class="group bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 open:ring-brand-200 transition">
                    <summary class="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer list-none font-semibold text-slate-900 select-none">
                        {{ $q }}
                        <span class="text-slate-400 group-open:rotate-45 transition-transform text-xl leading-none">+</span>
                    </summary>
                    <div class="px-5 pb-4 text-sm text-slate-600">{{ $a }}</div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="rounded-3xl bg-gradient-to-r from-brand-600 to-sky-500 text-white px-6 sm:px-12 py-10 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Siap bergabung bersama {{ config('psb.nama_sekolah') }}?</h2>
        <p class="mt-2 text-brand-50/90 text-sm sm:text-base">Kuota terbatas — daftarkan diri Anda sebelum periode ditutup.</p>
        <a href="{{ route('register') }}" class="mt-6 inline-block px-8 py-3 rounded-xl bg-white text-brand-700 font-bold text-sm sm:text-base shadow-lg hover:bg-brand-50 transition">Daftar Sekarang</a>
    </section>
@endsection
