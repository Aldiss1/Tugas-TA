@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Verifikasi Dokumen</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            {{-- Baris batch (form tersendiri, TIDAK membungkus tabel — hindari form bertingkat) --}}
            <div class="d-none mb-3" id="baris-batch">
                <form method="POST" action="{{ url('/admin/verifikasi/batch') }}" id="form-batch">
                    @csrf
                    <input type="hidden" name="aksi" value="lulus" id="aksi-batch">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold small" id="jumlah-pilihan">0 dipilih</span>
                        <button class="btn btn-sm btn-success" type="button" id="btn-batch-lulus">Luluskan Terpilih</button>
                        <button class="btn btn-sm btn-outline-danger" type="button" id="btn-batch-gugur-toggle">Gugurkan Terpilih…</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-bersihkan">Bersihkan</button>
                    </div>
                    <div class="mt-2" id="grup-gugur" style="display:none;">
                        <div class="input-group input-group-sm" style="max-width:420px;">
                            <input type="text" name="alasan" class="form-control"
                                placeholder="Alasan gugur untuk semua yang terpilih (wajib)" required>
                            <button class="btn btn-danger" type="submit">Gugurkan</button>
                        </div>
                    </div>
                </form>
            </div>

            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" class="form-check-input" id="cek-semua" title="Pilih semua halaman ini"></th>
                        <th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>Jurusan</th><th>Dokumen</th><th style="width:230px">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendaftar as $p)
                        @php $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value); @endphp
                        <tr data-baris="{{ $p->id }}">
                            <td><input type="checkbox" class="form-check-input cb-pilih" value="{{ $p->id }}"></td>
                            <td>{{ $p->nomor_pendaftaran ?? '—' }}</td>
                            <td>{{ $p->nama_lengkap }}</td>
                            <td>{{ $p->nisn }}</td>
                            <td>{{ $p->pilihan->first()?->jurusan?->kode ?? '—' }}</td>
                            <td>
                                @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                    <a href="{{ url('/admin/pendaftar/'.$p->id) }}" target="_blank"
                                        class="badge badge-{{ (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at) ? 'success' : 'secondary' }} text-decoration-none"
                                        title="{{ $jenis->label() }}">{{ strtoupper(substr($jenis->value, 0, 2)) }}</a>
                                @endforeach
                            </td>
                            <td>
                                <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}" class="d-inline"
                                    onsubmit="return confirm('Luluskan {{ addslashes($p->nama_lengkap) }}?');">
                                    @csrf
                                    <input type="hidden" name="aksi" value="lulus">
                                    <button class="btn btn-sm btn-success">Lulus</button>
                                </form>
                                <div class="d-inline">
                                    <button class="btn btn-sm btn-outline-danger btn-isi-alasan" type="button">Gugur</button>
                                </div>
                                <div class="alasan-inline mt-1" style="display:none;">
                                    <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}">
                                        @csrf
                                        <input type="hidden" name="aksi" value="gugur">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="alasan" class="form-control" style="width:150px"
                                                placeholder="Alasan (wajib)" required>
                                            <button class="btn btn-danger">Ya, gugur</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Tidak ada pendaftar dalam antrian verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $pendaftar->links() }}
        </div>
    </div>

    <script>
        (function () {
            var cbs = Array.prototype.slice.call(document.querySelectorAll('.cb-pilih'));
            var baris = document.getElementById('baris-batch');
            var jumlah = document.getElementById('jumlah-pilihan');
            var form = document.getElementById('form-batch');
            var aksi = document.getElementById('aksi-batch');
            var grupGugur = document.getElementById('grup-gugur');

            function hitung() {
                var n = cbs.filter(function (c) { return c.checked; }).length;
                baris.classList.toggle('d-none', n === 0);
                jumlah.textContent = n + ' dipilih';
            }

            cbs.forEach(function (c) { c.addEventListener('change', hitung); });
            var semua = document.getElementById('cek-semua');
            semua.addEventListener('change', function () {
                cbs.forEach(function (c) { c.checked = semua.checked; });
                hitung();
            });

            // kumpulkan id terpilih sebagai hidden input di form batch
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
                grupGugur.style.display = 'block';
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

            // tombol "Gugur" individual → tampilkan input alasan inline
            document.querySelectorAll('.btn-isi-alasan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var el = btn.closest('td').querySelector('.alasan-inline');
                    el.style.display = el.style.display === 'none' ? 'block' : 'none';
                });
            });
        })();
    </script>
@endsection
