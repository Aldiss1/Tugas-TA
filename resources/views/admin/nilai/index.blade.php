@extends('layouts.admin')

@section('title', 'Nilai & Ranking')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nilai & Ranking</h1>
        <form method="POST" action="{{ url('/admin/ranking/proses') }}" class="d-flex gap-2"
            onsubmit="return confirm('Proses ranking semua pendaftar lulus verifikasi? Hasil lama akan diganti.');">
            @csrf
            <button class="btn btn-primary"><i class="fas fa-calculator"></i> Proses Ranking</button>
        </form>
    </div>

    @if ($hasil->isNotEmpty())
        <div class="card shadow mb-4 border-left-success">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Hasil Ranking</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    Hasil ini <strong>belum resmi</strong> — baru terlihat oleh siswa setelah pengumuman diterbitkan
                    (menu Pengumuman). Tinjau sebelum menerbitkan.
                </p>
                <table class="table table-bordered table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Peringkat</th><th>Nama</th><th>No. Pendaftaran</th>
                            <th>Jurusan</th><th>Total</th><th>Status</th><th>Via</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hasil as $h)
                            <tr>
                                <td>{{ $h->perankingan ?? '—' }}</td>
                                <td>{{ $h->pendaftar?->nama_lengkap ?? '—' }}</td>
                                <td>{{ $h->pendaftar?->nomor_pendaftaran ?? '—' }}</td>
                                <td>{{ $h->jurusan?->kode ?? '—' }}</td>
                                <td>{{ $h->pendaftar?->nilai?->total ?? '—' }}</td>
                                <td>
                                    @if ($h->status_akhir === 'diterima')
                                        <span class="badge badge-success">Diterima</span>
                                    @elseif ($h->status_akhir === 'cadangan')
                                        <span class="badge badge-warning">Cadangan</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak Diterima</span>
                                    @endif
                                </td>
                                <td>P{{ $h->via_pilihan ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <h6 class="mb-2">Input Nilai (pendaftar lulus verifikasi)</h6>
            <p class="text-muted small">
                Bobot: rapor 40%, prestasi 30%, kunjungan sekolah 30%. Nilai dapat diubah selama tahap seleksi —
                setiap perubahan tercatat di riwayat.
            </p>
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No.</th><th>Nama</th><th>Jurusan</th>
                        <th>Nilai (Rapor / Prestasi / Kunjungan)</th><th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendaftar as $p)
                        <tr>
                            <td>{{ $p->nomor_pendaftaran ?? $p->id }}</td>
                            <td>{{ $p->nama_lengkap }}</td>
                            <td>{{ $p->pilihan->first()?->jurusan?->kode ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ url('/admin/nilai/'.$p->id) }}" class="d-flex gap-1 align-items-center">
                                    @csrf @method('PUT')
                                    <input type="number" step="0.01" min="0" max="100" name="rapor"
                                        value="{{ old('rapor', $p->nilai?->nilai_rapor ?? '') }}" class="form-control form-control-sm" style="width:75px" required
                                        title="Rapor (40%)">
                                    <input type="number" step="0.01" min="0" max="100" name="prestasi"
                                        value="{{ old('prestasi', $p->nilai?->nilai_prestasi ?? '') }}" class="form-control form-control-sm" style="width:75px" required
                                        title="Prestasi (30%)">
                                    <input type="number" step="0.01" min="0" max="100" name="kunjungan"
                                        value="{{ old('kunjungan', $p->nilai?->nilai_kunjungan ?? '') }}" class="form-control form-control-sm" style="width:75px" required
                                        title="Kunjungan (30%)">
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                            <td>
                                @if ($p->nilai)
                                    <strong>{{ $p->nilai->total }}</strong>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada pendaftar lulus verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $pendaftar->links() }}
        </div>
    </div>
@endsection
