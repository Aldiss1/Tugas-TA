@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan PSB</h1>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ url('/admin/laporan/export/csv') }}" class="d-flex gap-1">
                <select name="status" class="form-control form-control-sm">
                    <option value="">Semua status</option>
                    @foreach ($status as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-file-csv"></i> CSV</button>
            </form>
            <form method="GET" action="{{ url('/admin/laporan/export/xlsx') }}" class="d-flex gap-1">
                <select name="status" class="form-control form-control-sm">
                    <option value="">Semua status</option>
                    @foreach ($status as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel"></i> Excel</button>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        @foreach ([
            ['Pendaftar', $total['pendaftar'], 'primary'],
            ['Diterima', $total['diterima'], 'success'],
            ['Cadangan', $total['cadangan'], 'warning'],
            ['Tidak Diterima', $total['tidak_diterima'], 'secondary'],
        ] as [$label, $n, $color])
            <div class="col-md-3">
                <div class="card border-left-{{ $color }} shadow h-100 py-2">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-{{ $color }} text-uppercase">{{ $label }}</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $n }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Rekap per Jurusan</h6></div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr><th>Jurusan</th><th>Pendaftar</th><th>Diterima</th><th>Cadangan</th><th>Kuota</th><th>Sisa</th></tr>
                </thead>
                <tbody>
                    @foreach ($rekap as $kode => $r)
                        <tr>
                            <td><strong>{{ $kode }}</strong> — {{ $r['nama'] }}</td>
                            <td>{{ $r['pendaftar'] }}</td>
                            <td>{{ $r['terima'] }}</td>
                            <td>{{ $r['cadangan'] }}</td>
                            <td>{{ $r['kuota'] }}</td>
                            <td>{{ $r['sisa'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
