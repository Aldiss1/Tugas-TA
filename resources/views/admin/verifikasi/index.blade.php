@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen')

@section('content')
    <h2 class="text-2xl font-bold tracking-tight text-slate-900 mb-5">Verifikasi Dokumen</h2>

    {{-- Batch bar (muncul saat ada pilihan) --}}
    <div id="baris-batch" class="hidden sticky bottom-4 z-10 mb-4">
        <form id="form-batch" method="POST" action="{{ url('/admin/verifikasi/batch') }}"
            class="bg-slate-900 text-white rounded-2xl shadow-xl px-5 py-4">
            @csrf
            <input type="hidden" name="aksi" value="lulus" id="aksi-batch">
            <div class="flex items-center gap-3 flex-wrap">
                <span id="jumlah-pilihan" class="text-sm font-bold">0 dipilih</span>
                <button type="button" id="btn-batch-lulus"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 px-4 py-2 text-sm font-semibold text-white transition">
                    <x-icon-check class="h-4 w-4" /> Luluskan Terpilih
                </button>
                <button type="button" id="btn-batch-gugur-toggle"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-rose-500 hover:bg-rose-400 px-4 py-2 text-sm font-semibold text-white transition">
                    <x-icon-x-mark class="h-4 w-4" /> Gugurkan Terpilih…
                </button>
                <button type="button" id="btn-bersihkan"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 px-4 py-2 text-sm font-semibold text-slate-200 transition">
                    Bersihkan
                </button>
            </div>
            <div id="grup-gugur" class="hidden mt-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <input type="text" name="alasan"
                        class="rounded-lg bg-slate-800 border-slate-600 text-white text-sm shadow-sm focus:border-rose-400 focus:ring-rose-400"
                        style="max-width: 420px;" placeholder="Alasan gugur untuk semua terpilih (wajib)">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-rose-600 hover:bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition">Gugurkan</button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3 w-10">
                            <input type="checkbox" id="cek-semua" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                        </th>
                        <th class="px-5 py-3">Pendaftar</th>
                        <th class="px-5 py-3">NISN</th>
                        <th class="px-5 py-3">Jurusan</th>
                        <th class="px-5 py-3">Dokumen</th>
                        <th class="px-5 py-3 text-right">Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendaftar as $p)
                        @php
                            $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value);
                            $pl1 = $p->pilihan->firstWhere('prioritas', 1);
                        @endphp
                        <tr data-baris="{{ $p->id }}" class="hover:bg-slate-50/60 align-top">
                            <td class="px-5 py-3">
                                <input type="checkbox" class="cb-pilih rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4" value="{{ $p->id }}">
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-900">{{ $p->nama_lengkap }}</div>
                                <div class="text-xs font-mono text-slate-400">{{ $p->nomor_pendaftaran ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $p->nisn }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $pl1?->jurusan?->kode ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1">
                                    @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                        @php $d = $mapDok[$jenis->value] ?? null; @endphp
                                        <span class="h-2 w-2 rounded-full {{ ($d && $d->diunggah_at) ? 'bg-emerald-500' : 'bg-slate-200' }}" title="{{ $jenis->label() }}"></span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}" class="inline"
                                    onsubmit="return confirm('Luluskan {{ addslashes($p->nama_lengkap) }}?');">
                                    @csrf
                                    <input type="hidden" name="aksi" value="lulus">
                                    <button class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white transition">
                                        Lulus
                                    </button>
                                </form>
                                <button type="button" class="btn-isi-alasan inline-flex items-center gap-1.5 ml-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50 transition">
                                    Gugur
                                </button>
                                <div class="alasan-inline mt-2" style="display:none;">
                                    <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}">
                                        @csrf
                                        <input type="hidden" name="aksi" value="gugur">
                                        <div class="flex items-center gap-1.5">
                                            <input type="text" name="alasan" required placeholder="Alasan (wajib)"
                                                class="rounded-lg border-slate-300 text-xs shadow-sm focus:border-rose-400 focus:ring-rose-400" style="width: 170px;">
                                            <button class="inline-flex items-center rounded-lg bg-rose-600 hover:bg-rose-700 px-3 py-1.5 text-xs font-semibold text-white transition">
                                                Ya, gugur
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12">
                                <div class="text-center">
                                    <x-icon-clipboard-check class="h-10 w-10 text-slate-300 mx-auto" />
                                    <p class="mt-3 text-sm text-slate-500">Tidak ada pendaftar dalam antrian verifikasi.</p>
                                </div>
                            </td>
                        </tr>
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

    <script>
        (function () {
            var cbs = Array.prototype.slice.call(document.querySelectorAll('.cb-pilih'));
            var baris = document.getElementById('baris-batch');
            var jumlah = document.getElementById('jumlah-pilihan');
            var form = document.getElementById('form-batch');
            var aksi = document.getElementById('aksi-batch');
            var grupGugur = document.getElementById('grup-gugur');
            if (!cbs.length) return;

            function hitung() {
                var n = cbs.filter(function (c) { return c.checked; }).length;
                baris.classList.toggle('hidden', n === 0);
                jumlah.textContent = n + ' dipilih';
            }

            cbs.forEach(function (c) { c.addEventListener('change', hitung); });
            var semua = document.getElementById('cek-semua');
            semua.addEventListener('change', function () {
                cbs.forEach(function (c) { c.checked = semua.checked; });
                hitung();
            });

            function isiIds() {
                form.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
                cbs.filter(function (c) { return c.checked; }).forEach(function (c) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'ids[]';
                    h.value = c.value;
                    form.appendChild(h);
                });
            }

            document.getElementById('btn-batch-lulus').addEventListener('click', function () {
                if (!confirm('Luluskan pendaftar terpilih?')) return;
                aksi.value = 'lulus';
                grupGugur.style.display = 'none';
                isiIds();
                form.submit();
            });
            document.getElementById('btn-batch-gugur-toggle').addEventListener('click', function () {
                grupGugur.style.display = grupGugur.style.display === 'block' ? 'none' : 'block';
            });
            form.addEventListener('submit', function (e) {
                if (grupGugur.style.display === 'block') {
                    var alasan = form.querySelector('[name=alasan]');
                    if (!alasan.value.trim()) {
                        e.preventDefault();
                        alert('Alasan gugur wajib diisi.');
                        return;
                    }
                    aksi.value = 'gugur';
                    isiIds();
                    if (!confirm('Gugurkan pendaftar terpilih?')) e.preventDefault();
                }
            });
            document.getElementById('btn-bersihkan').addEventListener('click', function () {
                cbs.forEach(function (c) { c.checked = false; });
                semua.checked = false;
                hitung();
            });

            document.querySelectorAll('.btn-isi-alasan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var el = btn.closest('td').querySelector('.alasan-inline');
                    el.style.display = el.style.display === 'block' ? 'none' : 'block';
                });
            });
        })();
    </script>
@endsection
