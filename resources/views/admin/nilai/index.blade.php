@extends('layouts.admin')

@section('title', 'Nilai & Ranking')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nilai Seleksi</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
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
