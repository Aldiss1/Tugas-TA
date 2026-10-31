@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Verifikasi Dokumen</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ url('/admin/verifikasi/batch') }}" id="form-batch-wrap">
                @csrf
                <input type="hidden" name="aksi" value="lulus" id="aksi-batch">
                <input type="text" name="alasan" id="alasan-batch" class="d-none" placeholder="Alasan gugur massal">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:36px"><input type="checkbox" class="form-check-input" id="cek-semua"></th>
                            <th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>Jurusan</th><th>Dokumen</th><th style="width:230px">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendaftar as $p)
                            @php $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value); @endphp
                            <tr data-baris="{{ $p->id }}">
                                <td><input type="checkbox" class="form-check-input cb-pilih" name="ids[]" value="{{ $p->id }}"></td>
                                <td>{{ $p->nomor_pendaftaran ?? '—' }}</td>
                                <td>{{ $p->nama_lengkap }}</td>
                                <td>{{ $p->nisn }}</td>
                                <td>{{ $p->pilihan->first()?->jurusan?->kode ?? '—' }}</td>
                                <td>
                                    @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                        <a href="{{ url('/admin/pendaftar/'.$p->id) }}#tab-dokumen" target="_blank"
                                            class="badge badge-{{ (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at) ? 'success' : 'secondary' }} text-decoration-none"
                                            title="{{ $jenis->label() }}">{{ strtoupper(substr($jenis->value, 0, 2)) }}</a>
                                    @endforeach
                                </td>
                                <td>
                                    <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}" class="d-flex gap-1 align-items-center">
                                        @csrf
                                        <input type="hidden" name="aksi" value="lulus">
                                        <button class="btn btn-sm btn-success">Lulus</button>
                                    </form>
                                    <div class="d-flex gap-1 align-items-center">
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
                <div id="baris-batch" class="d-none mt-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold small" id="jumlah-pilihan">0 dipilih</span>
                        <button class="btn btn-sm btn-success" type="submit" id="btn-batch-lulus">Luluskan Terpilih</button>
                        <button class="btn btn-sm btn-outline-danger" type="button" id="btn-batch-gugur-toggle">Gugurkan Terpilih…</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-bersihkan">Bersihkan</button>
                    </div>
                    <div class="mt-2" id="grup-gugur" style="display:none;">
                        <div class="input-group input-group-sm" style="max-width:420px;">
                            <input type="text" name="alasan" class="form-control" placeholder="Alasan gugur untuk semua yang terpilih (wajib)" required>
                            <button class="btn btn-danger" type="submit">Gugurkan</button>
                        </div>
                    </div>
                </div>
            </form>
            {{ $pendaftar->links() }}
        </div>
    </div>

    <script>
        (function () {
            var cbs = Array.prototype.slice.call(document.querySelectorAll('.cb-pilih'));
            var baris = document.getElementById('baris-batch');
            var jumlah = document.getElementById('jumlah-pilihan');
            var form = document.getElementById('form-batch-wrap');
            var alasan = document.getElementById('alasan-batch');
            var grupGugur = document.getElementById('grup-gugur');
            var aksi = document.getElementById('aksi-batch');

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

            document.getElementById('btn-batch-lulus').addEventListener('click', function () {
                aksi.value = 'lulus';
                grupGugur.style.display = 'none';
                if (!confirm('Luluskan pendaftar terpilih?')) return;
            });
            document.getElementById('btn-batch-gugur-toggle').addEventListener('click', function () {
                grupGugur.style.display = 'block';
            });
            document.getElementById('btn-bersihkan').addEventListener('click', function () {
                cbs.forEach(function (c) { c.checked = false; });
                semua.checked = false;
                hitung();
            });

            // tombol "Gugur" individual → tampilkan input alasan inline
            document.querySelectorAll('.btn-isi-alasan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var baris = btn.closest('tr').querySelector('.alasan-inline');
                    baris.style.display = baris.style.display === 'none' ? 'block' : 'none';
                });
            });

            // submit batch: pastikan aksi sesuai tombol yang dipakai
            form.addEventListener('submit', function (e) {
                var n = cbs.filter(function (c) { return c.checked; }).length;
                if (n === 0) {
                    e.preventDefault();
                    return;
                }
                if (grupGugur.style.display === 'block' && alasan.value.trim() === '') {
                    e.preventDefault();
                    alert('Alasan gugur wajib diisi.');
                    return;
                }
                if (grupGugur.style.display === 'block') {
                    aksi.value = 'gugur';
                    if (!confirm('Gugurkan ' + n + ' pendaftar terpilih?')) e.preventDefault();
                }
            });
        })();
    </script>
@endsection
