@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard PSB {{ config('psb.tahun_ajaran') }}</h2>
            <p class="text-sm text-slate-500 mt-1">Pantau progress Penerimaan Siswa Baru dari pendaftaran sampai pengumuman.</p>
        </div>
    </div>

    {{-- Pipeline tahap --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                    <x-icon-user-plus class="h-5 w-5" />
                </span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tahap 1</span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-900">{{ $pipeline['pendaftar']['total'] }}</div>
            <div class="text-sm text-slate-500 -mt-1">Pendaftaran <span class="text-slate-400">({{ $pipeline['pendaftar']['draf'] }} draf)</span></div>
            <a href="{{ url('/admin/pendaftar') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                Lihat Pendaftar <span aria-hidden>→</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white">
                    <x-icon-clipboard-check class="h-5 w-5" />
                </span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tahap 2</span>
            </div>
            <div class="mt-4 text-3xl font-extrabold {{ $pipeline['verifikasi']['menunggu'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $pipeline['verifikasi']['menunggu'] }}</div>
            <div class="text-sm text-slate-500 -mt-1">Menunggu Verifikasi <span class="text-slate-400">({{ $pipeline['verifikasi']['lulus'] }} lulus • {{ $pipeline['verifikasi']['gugur'] }} gugur)</span></div>
            <a href="{{ url('/admin/verifikasi') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                Proses Verifikasi <span aria-hidden>→</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-sky-400 to-sky-600 text-white">
                    <x-icon-chart-bar class="h-5 w-5" />
                </span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tahap 3</span>
            </div>
            <div class="mt-4 text-3xl font-extrabold {{ $pipeline['nilai']['belum'] > 0 ? 'text-sky-600' : 'text-slate-900' }}">{{ $pipeline['nilai']['belum'] }}</div>
            <div class="text-sm text-slate-500 -mt-1">Belum Dinilai <span class="text-slate-400">({{ $pipeline['nilai']['ternilai'] }} dinilai)</span></div>
            <a href="{{ url('/admin/nilai') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                Input Nilai <span aria-hidden>→</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white">
                    <x-icon-bell class="h-5 w-5" />
                </span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tahap 4</span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-900">{{ $pipeline['hasil']['diterima'] }}</div>
            <div class="text-sm text-slate-500 -mt-1">Diterima <span class="text-slate-400">({{ $pipeline['hasil']['cadangan'] }} cadangan • {{ $pipeline['hasil']['tidak_diterima'] }} tidak diterima)</span></div>
            <div class="mt-3 flex items-center gap-2">
                @if ($pipeline['hasil']['terbit'])
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">Pengumuman: Terbit</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-500 ring-1 ring-slate-200">Pengumuman: Belum</span>
                @endif
                <a href="{{ url('/admin/pengumuman') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                    Pengumuman <span aria-hidden>→</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Grafik --}}
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <h3 class="font-bold text-slate-900 mb-4">Pendaftar 14 Hari Terakhir</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="chartPendaftar"></canvas>
            </div>
        </div>

        {{-- Kuota --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
            <h3 class="font-bold text-slate-900 mb-4">Kuota per Jurusan</h3>
            <ul class="space-y-4">
                @foreach ($jurusan as $j)
                    @php $terisi = $j->kuota->terisi ?? 0; $kuota = $j->kuota->kuota ?? 0; $pct = $kuota ? (int) round($terisi / $kuota * 100) : 0; @endphp
                    <li>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700 truncate">{{ $j->kode }} — {{ $j->nama }}</span>
                            <span class="text-xs text-slate-500 ml-2 shrink-0">{{ $terisi }}/{{ $kuota }} • Sisa: <strong class="text-slate-700">{{ $j->kuota->sisa() ?? 0 }}</strong></span>
                        </div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
@endpush

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
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#4F46E5',
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
                },
            });
        });
</script>
