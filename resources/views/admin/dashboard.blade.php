@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard PSB {{ config('psb.tahun_ajaran') }}</h1>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pendaftar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stat['total'] }}</div>
                        </div>
                        <div class="col"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Menunggu Verifikasi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stat['menunggu'] }}</div>
                        </div>
                        <div class="col"><i class="fas fa-hourglass-half fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Lulus Verifikasi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stat['lulus'] }}</div>
                        </div>
                        <div class="col"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Diterima</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stat['diterima'] }}</div>
                        </div>
                        <div class="col"><i class="fas fa-trophy fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Pendaftar 14 Hari Terakhir</h6></div>
                <div class="card-body"><canvas id="chartPendaftar" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Kuota per Jurusan</h6></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Jurusan</th><th>Kuota</th><th>Terisi</th><th>Sisa</th></tr></thead>
                        <tbody>
                            @foreach ($jurusan as $j)
                                <tr>
                                    <td>{{ $j->kode }} — {{ $j->nama }}</td>
                                    <td>{{ $j->kuota->kuota ?? 0 }}</td>
                                    <td>{{ $j->kuota->terisi ?? 0 }}</td>
                                    <td><strong>{{ $j->kuota->sisa() ?? 0 }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('Assets/Dashboard/vendor/chart.js/Chart.min.js') }}"></script>
    <script>
        fetch('{{ route('admin.dashboard.chart') }}')
            .then(r => r.json())
            .then(res => {
                new Chart(document.getElementById('chartPendaftar'), {
                    type: 'line',
                    data: {
                        labels: res.labels.map(l => l.substring(5)),
                        datasets: [{
                            label: 'Pendaftar',
                            data: res.data,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                        }],
                    },
                    options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                });
            });
    </script>
@endpush
