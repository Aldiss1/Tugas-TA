@extends('layouts.admin')

@section('title', 'Detail Pendaftar')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail: {{ $p->nama_lengkap }}</h1>
        <a href="{{ url('/admin/pendaftar') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-data" type="button">Data</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dokumen" type="button">Dokumen</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-nilai" type="button">Nilai</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button">Riwayat</button></li>
    </ul>

    <div class="tab-content">
        {{-- Data --}}
        <div class="tab-pane fade show active" id="tab-data">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th style="width:220px">Nomor Pendaftaran</th><td>{{ $p->nomor_pendaftaran ?? '—' }}</td></tr>
                        <tr><th>NISN</th><td>{{ $p->nisn }}</td></tr>
                        <tr><th>Sumber</th><td>{{ ucfirst($p->sumber) }}</td></tr>
                        <tr><th>Status</th><td><span class="badge badge-primary">{{ $p->status->label() }}</span></td></tr>
                        @if ($p->alasan_gugur)
                            <tr><th>Alasan Gugur</th><td class="text-danger">{{ $p->alasan_gugur }}</td></tr>
                        @endif
                        <tr><th>Tempat, Tgl. Lahir</th><td>{{ $p->tempat_lahir }}, {{ $p->tanggal_lahir->format('d M Y') }}</td></tr>
                        <tr><th>Jenis Kelamin / Agama</th><td>{{ $p->jenis_kelamin }} / {{ $p->agama }}</td></tr>
                        <tr><th>Asal Sekolah</th><td>{{ $p->asal_sekolah }}</td></tr>
                        <tr><th>Alamat</th><td>{{ $p->alamat }}</td></tr>
                        <tr><th>NIK</th><td>{{ $p->nik }}</td></tr>
                        <tr><th>Orang Tua / Wali</th><td>{{ $p->nama_orang_tua }} ({{ $p->pekerjaan_orang_tua }}) — {{ $p->telepon_orang_tua }}</td></tr>
                        <tr>
                            <th>Jurusan Pilihan</th>
                            <td>
                                @forelse ($p->pilihan as $pl)
                                    <span class="badge badge-light border">P{{ $pl->prioritas }}: {{ $pl->jurusan->nama }} ({{ $pl->jurusan->kode }})</span>
                                @empty
                                    —
                                @endforelse
                            </td>
                        </tr>
                        @if ($p->hasil)
                            <tr>
                                <th>Hasil</th>
                                <td>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $p->hasil->status_akhir)) }}</strong>
                                    @if ($p->hasil->jurusan)
                                        — {{ $p->hasil->jurusan->nama }}, peringkat {{ $p->hasil->perankingan }} (via pilihan {{ $p->hasil->via_pilihan }})
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Dokumen --}}
        <div class="tab-pane fade" id="tab-dokumen">
            <div class="card shadow mb-4">
                <div class="card-body">
                    @php $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value); @endphp
                    <table class="table table-sm">
                        <thead><tr><th>Dokumen</th><th>Terunggah</th><th>Ukuran</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($jenisDokumen as $jenis)
                                @php $dok = $mapDok[$jenis->value] ?? null; @endphp
                                <tr>
                                    <td>{{ $jenis->label() }}</td>
                                    <td>{{ $dok?->diunggah_at?->format('d M Y H:i') ?? '—' }}</td>
                                    <td>{{ $dok ? round($dok->ukuran_bytes / 1024, 1).' KB' : '—' }}</td>
                                    <td>
                                        @if ($dok?->diunggah_at)
                                            <a class="btn btn-sm btn-info" href="{{ url('/admin/pendaftar/'.$p->id.'/dokumen/'.$jenis->value) }}" target="_blank"><i class="fas fa-eye"></i> Preview</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Nilai --}}
        <div class="tab-pane fade" id="tab-nilai">
            <div class="card shadow mb-4">
                <div class="card-body">
                    @if ($p->nilai)
                        <table class="table table-sm">
                            <tr><th>Nilai Rapor (40%)</th><td>{{ $p->nilai->nilai_rapor }}</td></tr>
                            <tr><th>Nilai Prestasi (30%)</th><td>{{ $p->nilai->nilai_prestasi }}</td></tr>
                            <tr><th>Nilai Kunjungan (30%)</th><td>{{ $p->nilai->nilai_kunjungan }}</td></tr>
                            <tr><th>Total</th><td><strong>{{ $p->nilai->total }}</strong></td></tr>
                        </table>
                    @else
                        <p class="text-muted mb-0">Belum ada nilai. Input melalui menu <a href="{{ url('/admin/nilai') }}">Nilai & Ranking</a>.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="tab-pane fade" id="tab-riwayat">
            <div class="card shadow mb-4">
                <div class="card-body">
                    @forelse ($riwayat as $r)
                        <div class="border-bottom py-2">
                            <strong>{{ $r->aksi }}</strong> — {{ $r->created_at?->format('d M Y H:i') }}
                            <span class="text-muted">oleh {{ $r->pengguna?->name ?? 'Sistem' }}</span>
                            @if ($r->detail)
                                <div class="small text-muted">{{ json_encode($r->detail) }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada aktivitas tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
