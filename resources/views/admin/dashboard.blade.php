@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard PSB {{ config('psb.tahun_ajaran') }}</h1>
    </div>

    {{-- Pipeline tahap --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-primary fw-bold">1 • Pendaftaran</div>
                    <div class="h4 mb-1">{{ $pipeline['pendaftar']['total'] }}</div>
                    <div class="small text-muted">{{ $pipeline['pendaftar']['draf'] }} masih draf</div>
                    <a href="{{ url('/admin/pendaftar') }}" class="btn btn-sm btn-outline-primary mt-2">Lihat Pendaftar</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-warning fw-bold">2 • Verifikasi</div>
                    <div class="h4 mb-1 {{ $pipeline['verifikasi']['menunggu'] > 0 ? 'text-warning' : '' }}">
                        {{ $pipeline['verifikasi']['menunggu'] }} <span class="small text-muted">menunggu</span>
                    </div>
                    <div class="small text-muted">{{ $pipeline['verifikasi']['lulus'] }} lulus • {{ $pipeline['verifikasi']['gugur'] }} gugur</div>
                    <a href="{{ url('/admin/verifikasi') }}" class="btn btn-sm btn-outline-warning mt-2">Proses Verifikasi</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-info fw-bold">3 • Nilai</div>
                    <div class="h4 mb-1 {{ $pipeline['nilai']['belum'] > 0 ? 'text-info' : '' }}">{{ $pipeline['nilai']['belum'] }} <span class="small text-muted">belum dinilai</span></div>
                    <div class="small text-muted">{{ $pipeline['nilai']['ternilai'] }} sudah dinilai</div>
                    <a href="{{ url('/admin/nilai') }}" class="btn btn-sm btn-outline-info mt-2">Input Nilai</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="small text-uppercase text-success fw-bold">4 • Hasil</div>
                    <div class="h4 mb-1">{{ $pipeline['hasil']['diterima'] }} <span class="small text-muted">diterima</span></div>
                    <div class="small text-muted">
                        {{ $pipeline['hasil']['cadangan'] }} cadangan • {{ $pipeline['hasil']['tidak_diterima'] }} tidak diterima
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        @if ($pipeline['hasil']['terbit'])
                            <span class="badge badge-success">Pengumuman: Terbit</span>
                        @else
                            <span class="badge badge-secondary">Pengumuman: Belum</span>
                        @endif
                        <a href="{{ url('/admin/pengumuman') }}" class="btn btn-sm btn-outline-success">Pengumuman</a>
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
