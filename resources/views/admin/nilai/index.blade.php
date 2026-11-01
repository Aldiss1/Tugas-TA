@extends('layouts.admin')

@section('title', 'Nilai & Ranking')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Nilai & Ranking</h2>
        <form method="POST" action="{{ url('/admin/ranking/proses') }}"
            onsubmit="return confirm('Proses ranking semua pendaftar lulus verifikasi? Hasil lama akan diganti.');">
            @csrf
            <button @disabled($sudahTerbit)
                class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon-chart-bar class="h-4 w-4" /> Proses Ranking
            </button>
        </form>
    </div>

    @if ($hasil->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <h3 class="font-bold text-slate-900">Hasil Ranking</h3>
                @if (! $sudahTerbit)
                    <x-alert variant="warning" class="!py-1.5 !px-3 text-xs">belum resmi — akan terlihat oleh siswa setelah pengumuman diterbitkan</x-alert>
                @else
                    <x-alert variant="success" class="!py-1.5 !px-3 text-xs">resmi & terkunci</x-alert>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3">Peringkat</th>
                            <th class="px-5 py-3">Nama</th>
                            <th class="px-5 py-3">No. Pendaftaran</th>
                            <th class="px-5 py-3">Jurusan</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-center">Via</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($hasil as $h)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    @if ($h->perankingan)
                                        <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">{{ $h->perankingan }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-900">{{ $h->pendaftar?->nama_lengkap ?? '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $h->pendaftar?->nomor_pendaftaran ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $h->jurusan?->kode ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-semibold text-slate-900">{{ $h->pendaftar?->nilai?->total ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($h->status_akhir === 'diterima')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Diterima</span>
                                    @elseif ($h->status_akhir === 'cadangan')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Cadangan</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">Tidak Diterima</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center text-xs font-semibold text-slate-500">P{{ $h->via_pilihan ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h3 class="font-bold text-slate-900">Input Nilai (pendaftar lulus verifikasi)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Bobot: rapor 40% • prestasi 30% • kunjungan 30%. Sel berubah di-highlight — Simpan Semua untuk menyimpan.</p>
            </div>
            @if (! $sudahTerbit)
                <div class="flex items-center gap-2">
                    <span id="err-batch" class="text-rose-600 text-xs font-semibold hidden"></span>
                    <button type="button" id="btn-simpan-semua"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition">
                        <x-icon-check class="h-4 w-4" /> Simpan Semua
                    </button>
                    <button type="button" id="btn-batal"
                        class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-100 transition">
                        Batal
                    </button>
                </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">No.</th>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Jurusan</th>
                        <th class="px-5 py-3 text-center">Rapor</th>
                        <th class="px-5 py-3 text-center">Prestasi</th>
                        <th class="px-5 py-3 text-center">Kunjungan</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendaftar as $p)
                        @php $n = $p->nilai; @endphp
                        <tr data-baris="{{ $p->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $p->nomor_pendaftaran ?? $p->id }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $p->nama_lengkap }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $p->pilihan->first()?->jurusan?->kode ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="number" step="0.01" min="0" max="100" data-kolom="rapor" data-asli="{{ $n?->nilai_rapor ?? '' }}"
                                    value="{{ $n?->nilai_rapor ?? '' }}" @disabled($sudahTerbit)
                                    class="in-nilai w-20 text-center rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="number" step="0.01" min="0" max="100" data-kolom="prestasi" data-asli="{{ $n?->nilai_prestasi ?? '' }}"
                                    value="{{ $n?->nilai_prestasi ?? '' }}" @disabled($sudahTerbit)
                                    class="in-nilai w-20 text-center rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="number" step="0.01" min="0" max="100" data-kolom="kunjungan" data-asli="{{ $n?->nilai_kunjungan ?? '' }}"
                                    value="{{ $n?->nilai_kunjungan ?? '' }}" @disabled($sudahTerbit)
                                    class="in-nilai w-20 text-center rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="total-nilai font-mono font-bold text-slate-900">{{ $n?->total ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada pendaftar lulus verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pendaftar->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap gap-1.5">
                @if ($pendaftar->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50">«</span>
                @else
                    <a href="{{ $pendaftar->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">«</a>
                @endif
                @for ($i = max(1, $pendaftar->currentPage() - 2); $i <= min($pendaftar->lastPage(), $pendaftar->currentPage() + 2); $i++)
                    @if ($i === $pendaftar->currentPage())
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-brand-600">{{ $i }}</span>
                    @else
                        <a href="{{ $pendaftar->url($i) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">{{ $i }}</a>
                    @endif
                @endfor
                @if ($pendaftar->onLastPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50">»</span>
                @else
                    <a href="{{ $pendaftar->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">»</a>
                @endif
            </div>
        @endif
    </div>

    @if (! $sudahTerbit)
        <script>
            (function () {
                var BOBOT = @json(config('psb.bobot'));
                var dirty = [];

                function hitungTotal(tr) {
                    var r = parseFloat(tr.querySelector('[data-kolom=rapor]').value);
                    var p = parseFloat(tr.querySelector('[data-kolom=prestasi]').value);
                    var k = parseFloat(tr.querySelector('[data-kolom=kunjungan]').value);
                    var el = tr.querySelector('.total-nilai');
                    if (isNaN(r) || isNaN(p) || isNaN(k)) { el.textContent = '—'; return; }
                    el.textContent = (BOBOT.rapor * r + BOBOT.prestasi * p + BOBOT.kunjungan * k).toFixed(2);
                }

                function tandai(tr) {
                    var inputs = tr.querySelectorAll('.in-nilai');
                    var berubah = false;
                    inputs.forEach(function (el) {
                        var beda = (el.value === '' ? '0' : el.value) !== (el.dataset.asli === '' ? '0' : el.dataset.asli);
                        el.classList.toggle('ring-2', beda);
                        el.classList.toggle('ring-amber-400', beda);
                        el.classList.toggle('border-amber-400', beda);
                        if (beda) berubah = true;
                    });
                    if (berubah && dirty.indexOf(tr) === -1) dirty.push(tr);
                    if (!berubah) {
                        var i = dirty.indexOf(tr);
                        if (i > -1) dirty.splice(i, 1);
                    }
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
                    document.getElementById('err-batch').classList.add('hidden');
                });

                document.getElementById('btn-simpan-semua').addEventListener('click', function () {
                    var rows = [];
                    var dilewati = [];
                    dirty.forEach(function (tr) {
                        var kosong = false;
                        tr.querySelectorAll('.in-nilai').forEach(function (el) {
                            if (el.value === '') kosong = true;
                        });
                        if (kosong) {
                            dilewati.push(tr.cells[1].textContent.trim());
                            return;
                        }
                        rows.push({
                            id: parseInt(tr.dataset.baris, 10),
                            rapor: parseFloat(tr.querySelector('[data-kolom=rapor]').value) || 0,
                            prestasi: parseFloat(tr.querySelector('[data-kolom=prestasi]').value) || 0,
                            kunjungan: parseFloat(tr.querySelector('[data-kolom=kunjungan]').value) || 0,
                        });
                    });
                    if (dilewati.length) {
                        alert('Lengkapi ketiga nilai pada baris: ' + dilewati.join(', ') + ' (atau klik Batal).');
                    }
                    if (!rows.length) {
                        if (!dilewati.length) alert('Tidak ada nilai yang berubah.');
                        return;
                    }
                    var token = document.querySelector('meta[name=csrf-token]').content;
                    fetch('{{ url('/admin/nilai/batch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ rows: rows }),
                    }).then(function (r) {
                        return r.json().then(function (j) { return { ok: r.ok, j: j }; });
                    }).then(function (res) {
                        var err = document.getElementById('err-batch');
                        if (res.ok && res.j.ok) {
                            err.classList.add('hidden');
                            err.textContent = '';
                            alert(res.j.tersimpan + ' nilai disimpan.');
                            document.querySelectorAll('.in-nilai').forEach(function (el) { el.dataset.asli = el.value; });
                            dirty = [];
                            document.querySelectorAll('tr[data-baris]').forEach(function (tr) {
                                tr.querySelectorAll('.in-nilai').forEach(function (el) {
                                    el.classList.remove('ring-2', 'ring-amber-400', 'border-amber-400');
                                });
                            });
                        } else {
                            err.classList.remove('hidden');
                            var pesan = (res.j && (res.j.error || res.j.message)) || 'Gagal menyimpan.';
                            if (res.j && res.j.baris) {
                                pesan += ' — ' + res.j.baris.map(function (b) { return b.error; }).join('; ');
                            }
                            err.textContent = pesan;
                        }
                    }).catch(function () {
                        var err = document.getElementById('err-batch');
                        err.classList.remove('hidden');
                        err.textContent = 'Gagal terhubung ke server.';
                    });
                });
            })();
        </script>
    @endif
@endsection
