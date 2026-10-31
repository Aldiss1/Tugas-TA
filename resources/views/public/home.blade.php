@extends('layouts.public')

@section('title', 'Beranda PSB')

@section('content')

    {{-- Banner --}}
    <div class="p-4 text-white rounded-4 mb-4" style="background: linear-gradient(120deg, #103cbe, #2b6cf0);">
        <h1 class="display-6">Penerimaan Siswa Baru {{ config('psb.tahun_ajaran') }}</h1>
        <p class="mb-2">{{ config('psb.nama_sekolah') }} — Akreditasi A • 7 Program Keahlian</p>
        @if ($tahapAktif)
            <span class="badge bg-light text-dark fs-6">Tahap aktif: {{ $tahapAktif->label() }}</span>
        @else
            <span class="badge bg-warning text-dark fs-6">Tidak ada tahap aktif saat ini</span>
        @endif
        <div class="mt-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg me-2">Daftar Sekarang</a>
            <a href="{{ route('cek-status') }}" class="btn btn-outline-light btn-lg">Cek Status</a>
        </div>
    </div>

    {{-- Alur pendaftaran --}}
    <div class="row g-3 mb-4">
        @foreach ([
            ['1', 'Daftar Akun', 'Buat akun dengan email dan NISN, lalu verifikasi email.'],
            ['2', 'Isi Form & Dokumen', 'Lengkapi data diri, pilih jurusan, unggah 5 dokumen.'],
            ['3', 'Verifikasi & Seleksi', 'Admin memverifikasi dokumen dan memproses nilai seleksi.'],
            ['4', 'Pengumuman', 'Pantau status dan cetak surat keterangan diterima.'],
        ] as $i => [$no, $judul, $isi])
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="fs-2 fw-bold text-primary">{{ $no }}</div>
                        <h6 class="card-title">{{ $judul }}</h6>
                        <p class="card-text small text-muted mb-0">{{ $isi }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Jurusan --}}
    <h4 class="mb-3">Program Keahlian</h4>
    <div class="row g-3 mb-4" id="jurusan">
        @foreach ($jurusan as $j)
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">{{ $j->nama }} <span class="badge bg-primary">{{ $j->kode }}</span></h6>
                        <p class="small text-muted">{{ $j->deskripsi }}</p>
                        <div class="small">
                            Kuota: {{ $j->kuota->kuota ?? 0 }} • Terisi: {{ $j->kuota->terisi ?? 0 }} •
                            Sisa: <strong>{{ $j->kuota->sisa() ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Kalender PSB --}}
    <h4 class="mb-3" id="jadwal">Kalender PSB</h4>
    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tahap</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statusJadwal as $s)
                        <tr>
                            <td>{{ $s['label'] }}</td>
                            <td>{{ $s['mulai'] ?? '—' }}</td>
                            <td>{{ $s['selesai'] ?? '—' }}</td>
                            <td>
                                @if ($s['aktif'])
                                    <span class="badge bg-success">Berlangsung</span>
                                @else
                                    <span class="badge bg-secondary">Tidak aktif</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- FAQ --}}
    <h4 class="mb-3">FAQ</h4>
    <div class="accordion mb-4" id="faq">
        @foreach ([
            ['Apa saja dokumen yang harus diunggah?', 'KTP, Kartu Keluarga, ijazah/surat kelulusan, rapor semester terakhir, dan foto 3x4 (PDF/JPG/PNG, maks. 2 MB).'],
            ['Bisa memilih berapa jurusan?', 'Maksimal 2 jurusan: prioritas 1 (wajib) dan prioritas 2 (opsional).'],
            ['Bagaimana jika kuota prioritas 1 penuh?', 'Sistem melanjutkan proses ke jurusan prioritas 2 sesuai sisa kuota.'],
            ['Kapan pengumuman?', 'Mengikuti kalender PSB di atas. Pantau terus status Anda.'],
        ] as [$q, $a])
            <div class="accordion-item">
                <h6 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $loop->index }}">
                        {{ $q }}
                    </button>
                </h6>
                <div id="faq{{ $loop->index }}" class="accordion-collapse collapse" data-bs-parent="#faq">
                    <div class="accordion-body small">{{ $a }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
