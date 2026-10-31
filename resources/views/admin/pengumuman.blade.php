@extends('layouts.admin')

@section('title', 'Pengumuman')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Pengumuman PSB {{ config('psb.tahun_ajaran') }}</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if ($terbit)
                <div class="alert alert-success">
                    <strong>Pengumuman TERBIT</strong> pada {{ $waktu?->format('d M Y H:i') }}.
                    Nilai dan ranking terkunci — calon siswa sudah dapat melihat hasil.
                </div>
            @else
                <div class="alert alert-warning">
                    Pengumuman belum terbit. Pastikan proses ranking sudah selesai dan ditinjau.
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="text-uppercase small">Diterima</div>
                            <div class="h3 mb-0">{{ $stat['diterima'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="text-uppercase small">Cadangan</div>
                            <div class="h3 mb-0">{{ $stat['cadangan'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="text-uppercase small">Tidak Diterima</div>
                            <div class="h3 mb-0">{{ $stat['tidak_diterima'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="mt-4 mb-2">Rekap per Jurusan</h6>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Jurusan</th><th>Kuota</th><th>Terisi</th><th>Cadangan</th></tr></thead>
                <tbody>
                    @foreach ($jurusan as $j)
                        <tr>
                            <td>{{ $j->kode }} — {{ $j->nama }}</td>
                            <td>{{ $j->kuota->kuota ?? 0 }}</td>
                            <td>{{ $j->kuota->terisi ?? 0 }}</td>
                            <td>{{ $j->kuota->cadangan ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if (! $terbit)
                <form method="POST" action="{{ url('/admin/pengumuman/terbit') }}"
                    onsubmit="return confirm('Terbitkan pengumuman? Tindakan ini mengunci semua data seleksi.');">
                    @csrf
                    <button class="btn btn-success"><i class="fas fa-bullhorn"></i> Terbitkan Pengumuman</button>
                </form>
            @endif
        </div>
    </div>
@endsection
