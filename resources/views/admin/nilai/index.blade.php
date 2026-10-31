@extends('layouts.admin')

@section('title', 'Nilai & Ranking')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nilai & Ranking</h1>
        <form method="POST" action="{{ url('/admin/ranking/proses') }}" class="d-flex gap-2"
            onsubmit="return confirm('Proses ranking semua pendaftar lulus verifikasi? Hasil lama akan diganti.');">
            @csrf
            <button class="btn btn-primary" @disabled($sudahTerbit)><i class="fas fa-calculator"></i> Proses Ranking</button>
        </form>
    </div>

    @if ($hasil->isNotEmpty())
        <div class="card shadow mb-4 border-left-success">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Hasil Ranking</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    @if (! $sudahTerbit)
                        Hasil ini <strong>belum resmi</strong> — baru terlihat oleh siswa setelah pengumuman
                        diterbitkan (menu Pengumuman). Tinjau sebelum menerbitkan.
                    @else
                        Pengumuman terbit — hasil di bawah resmi & terkunci.
                    @endif
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
        <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="m-0 font-weight-bold text-primary">Input Nilai (pendaftar lulus verifikasi)</h6>
            <div class="d-flex gap-2" @if ($sudahTerbit) style="display:none" @endif>
                <button class="btn btn-sm btn-primary" id="btn-simpan-semua" type="button"><i class="fas fa-save"></i> Simpan Semua</button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-batal" type="button">Batal</button>
                <span class="text-danger small d-none align-self-center" id="err-batch"></span>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted small">
                Bobot: rapor 40%, prestasi 30%, kunjungan sekolah 30%. Edit sel langsung (Tab untuk pindah sel),
                sel berubah di-highlight, lalu <strong>Simpan Semua</strong>. Setiap perubahan tercatat di riwayat & audit.
            </p>
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th>No.</th><th>Nama</th><th>Jurusan</th>
                        <th style="width:90px">Rapor</th><th style="width:90px">Prestasi</th><th style="width:90px">Kunjungan</th>
                        <th style="width:80px">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendaftar as $p)
                        <tr data-baris="{{ $p->id }}">
                            <td class="text-nowrap">{{ $p->nomor_pendaftaran ?? $p->id }}</td>
                            <td>{{ $p->nama_lengkap }}</td>
                            <td>{{ $p->pilihan->first()?->jurusan?->kode ?? '—' }}</td>
                            @php
                                $n = $p->nilai;
                            @endphp
                            <td>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm in-nilai"
                                    data-kolom="rapor" data-asli="{{ $n?->nilai_rapor ?? '' }}"
                                    value="{{ $n?->nilai_rapor ?? '' }}" @disabled($sudahTerbit)>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm in-nilai"
                                    data-kolom="prestasi" data-asli="{{ $n?->nilai_prestasi ?? '' }}"
                                    value="{{ $n?->nilai_prestasi ?? '' }}" @disabled($sudahTerbit)>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm in-nilai"
                                    data-kolom="kunjungan" data-asli="{{ $n?->nilai_kunjungan ?? '' }}"
                                    value="{{ $n?->nilai_kunjungan ?? '' }}" @disabled($sudahTerbit)>
                            </td>
                            <td class="total-nilai fw-semibold">{{ $n?->total ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada pendaftar lulus verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $pendaftar->links() }}
        </div>
    </div>

    <script>
        @if (! $sudahTerbit)
        (function () {
            var BOBOT = { rapor: {{ config('psb.bobot.rapor') }}, prestasi: {{ config('psb.bobot.prestasi') }}, kunjungan: {{ config('psb.bobot.kunjungan') }} };

            function barisInput(tr) {
                return {
                    id: tr.dataset.baris,
                    rapor: tr.querySelector('[data-kolom=rapor]'),
                    prestasi: tr.querySelector('[data-kolom=prestasi]'),
                    kunjungan: tr.querySelector('[data-kolom=kunjungan]'),
                };
            }

            function hitungTotal(tr) {
                var b = barisInput(tr);
                var r = parseFloat(b.rapor.value), p = parseFloat(b.prestasi.value), k = parseFloat(b.kunjungan.value);
                var el = tr.querySelector('.total-nilai');
                if (isNaN(r) || isNaN(p) || isNaN(k)) { el.textContent = '—'; return; }
                el.textContent = (BOBOT.rapor * r + BOBOT.prestasi * p + BOBOT.kunjungan * k).toFixed(2);
            }

            function tandai(tr) {
                var b = barisInput(tr);
                var berubah = [b.rapor, b.prestasi, b.kunjungan].some(function (el) {
                    return el.value !== el.dataset.asli;
                });
                [b.rapor, b.prestasi, b.kunjungan].forEach(function (el) {
                    el.classList.toggle('border-danger', berubah);
                    el.classList.toggle('bg-warning', berubah);
                });
            }

            document.querySelectorAll('.in-nilai').forEach(function (el) {
                el.addEventListener('input', function () {
                    var tr = el.closest('tr');
                    hitungTotal(tr);
                    tandai(tr);
                });
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        var inputs = Array.prototype.slice.call(document.querySelectorAll('.in-nilai'));
                        var i = inputs.indexOf(el);
                        var berikutnya = inputs[i + 1] || inputs[i - 3];
                        if (berikutnya) berikutnya.focus();
                    }
                });
            });

            document.getElementById('btn-batal').addEventListener('click', function () {
                document.querySelectorAll('.in-nilai').forEach(function (el) {
                    el.value = el.dataset.asli;
                    var tr = el.closest('tr');
                    hitungTotal(tr);
                    tandai(tr);
                });
                document.getElementById('err-batch').classList.add('d-none');
            });

            document.getElementById('btn-simpan-semua').addEventListener('click', function () {
                var rows = [];
                document.querySelectorAll('tr[data-baris]').forEach(function (tr) {
                    var b = barisInput(tr);
                    var berubah = [b.rapor, b.prestasi, b.kunjungan].some(function (el) { return el.value !== el.dataset.asli; });
                    if (!berubah) return;
                    if (b.rapor.value === '' || b.prestasi.value === '' || b.kunjungan.value === '') {
                        alert('Lengkapi ketiga nilai pada baris ' + tr.cells[1].textContent + ' (atau klik Batal).');
                        return;
                    }
                    rows.push({
                        id: parseInt(b.id, 10),
                        rapor: parseFloat(b.rapor.value),
                        prestasi: parseFloat(b.prestasi.value),
                        kunjungan: parseFloat(b.kunjungan.value),
                    });
                });
                if (!rows.length) { alert('Tidak ada nilai yang berubah.'); return; }

                fetch('{{ url('/admin/nilai/batch') }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                    },
                    body: JSON.stringify({ rows: rows }),
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                    .then(function (res) {
                        if (res.ok) {
                            document.querySelectorAll('tr[data-baris]').forEach(function (tr) {
                                tr.querySelectorAll('.in-nilai').forEach(function (el) { el.dataset.asli = el.value; });
                                tr.querySelectorAll('.in-nilai').forEach(function (el) { el.classList.remove('border-danger', 'bg-warning'); });
                            });
                            alert(res.j.tersimpan + ' nilai disimpan.');
                        } else {
                            var pesan = res.j.error || 'Gagal menyimpan.';
                            if (res.j.baris) {
                                pesan += ' — ' + res.j.baris.map(function (b) { return b.error; }).join('; ');
                            }
                            var el = document.getElementById('err-batch');
                            el.textContent = pesan;
                            el.classList.remove('d-none');
                        }
                    })
                    .catch(function () { alert('Gagal terhubung ke server.'); });
            });
        })();
        @endif
    </script>
@endsection
