@extends('layouts.portal')

@section('title', 'Pendaftaran')

@section('content')
    <div class="row g-4">
        {{-- ===== FORM ===== --}}
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h3 class="mb-0">Formulir Pendaftaran</h3>
                @if ($p?->nomor_pendaftaran)
                    <span class="badge bg-primary fs-6">No. Pendaftaran: {{ $p->nomor_pendaftaran }}</span>
                @endif
            </div>

            <form method="POST" action="{{ route('pendaftaran') }}" id="form-pendaftaran" novalidate>
                @csrf

                {{-- 1. Data Diri --}}
                <div class="card border-0 shadow-sm mb-4" id="seksi-data">
                    <div class="card-header bg-white py-2"><h6 class="mb-0"><span class="text-primary">1.</span> Data Diri</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $p?->nama_lengkap) }}" class="form-control" required>
                                @error('nama_lengkap') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $p?->tanggal_lahir?->format('Y-m-d')) }}" class="form-control" required>
                                @error('tanggal_lahir') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $p?->tempat_lahir) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" class="form-select" required>
                                    <option value="">—</option>
                                    <option value="L" @selected(old('jenis_kelamin', $p?->jenis_kelamin) === 'L')>Laki-laki</option>
                                    <option value="P" @selected(old('jenis_kelamin', $p?->jenis_kelamin) === 'P')>Perempuan</option>
                                </select>
                                @error('jenis_kelamin') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Agama</label>
                                <input type="text" name="agama" value="{{ old('agama', $p?->agama) }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asal Sekolah (SMP/MTs)</label>
                                <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah', $p?->asal_sekolah) }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK</label>
                                <input type="text" name="nik" value="{{ old('nik', $p?->nik) }}" class="form-control" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" rows="2" class="form-control">{{ old('alamat', $p?->alamat) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">NISN</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->nisn }}" disabled>
                                <div class="form-text">NISN dari akun — tidak dapat diubah.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Orang Tua / Wali --}}
                <div class="card border-0 shadow-sm mb-4" id="seksi-ortu">
                    <div class="card-header bg-white py-2"><h6 class="mb-0"><span class="text-primary">2.</span> Orang Tua / Wali</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama</label>
                                <input type="text" name="nama_orang_tua" value="{{ old('nama_orang_tua', $p?->nama_orang_tua) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pekerjaan</label>
                                <input type="text" name="pekerjaan_orang_tua" value="{{ old('pekerjaan_orang_tua', $p?->pekerjaan_orang_tua) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="telepon_orang_tua" value="{{ old('telepon_orang_tua', $p?->telepon_orang_tua) }}" class="form-control" maxlength="20">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Pilihan Jurusan --}}
                <div class="card border-0 shadow-sm mb-4" id="seksi-jurusan">
                    <div class="card-header bg-white py-2"><h6 class="mb-0"><span class="text-primary">3.</span> Pilihan Jurusan</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilihan 1 (wajib) <span class="text-danger">*</span></label>
                                <select name="pilihan_1" class="form-select" required>
                                    <option value="">— pilih jurusan —</option>
                                    @foreach ($jurusan as $j)
                                        <option value="{{ $j->id }}" @selected((int) old('pilihan_1', $p?->pilihan()->where('prioritas', 1)->first()?->jurusan_id) === $j->id)>
                                            {{ $j->nama }} — sisa {{ $j->kuota->sisa() ?? 0 }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pilihan_1') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pilihan 2 (opsional)</label>
                                <select name="pilihan_2" class="form-select">
                                    <option value="">— tidak ada —</option>
                                    @foreach ($jurusan as $j)
                                        <option value="{{ $j->id }}" @selected((int) old('pilihan_2', $p?->pilihan()->where('prioritas', 2)->first()?->jurusan_id) === $j->id)>
                                            {{ $j->nama }} — sisa {{ $j->kuota->sisa() ?? 0 }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilihan 2 harus berbeda dari Pilihan 1.</div>
                                @error('pilihan_2') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Dokumen --}}
                <div class="card border-0 shadow-sm mb-4" id="seksi-dokumen">
                    <div class="card-header bg-white py-2"><h6 class="mb-0"><span class="text-primary">4.</span> Dokumen Persyaratan</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">PDF / JPG / PNG, maksimal 2 MB per file. Tersimpan otomatis saat file dipilih.</p>
                        @php
                            $mapDok = $p?->dokumen()->get()->keyBy(fn ($d) => $d->jenis->value);
                        @endphp
                        <div class="row g-3">
                            @foreach ($jenisDokumen as $jenis)
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small fw-semibold">{{ $jenis->label() }}</span>
                                                <span id="slot-{{ $jenis->value }}" class="badge @if (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at) bg-success @else bg-secondary @endif">
                                                    @if (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at) ✓ Tersimpan @else Belum @endif
                                                </span>
                                            </div>
                                            <input type="file" data-jenis="{{ $jenis->value }}"
                                                form="form-dok-{{ $jenis->value }}" name="file"
                                                class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                            <div id="err-{{ $jenis->value }}" class="text-danger small mt-1"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('file')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap mb-4">
                    <button class="btn btn-outline-primary" type="submit">Simpan Draf</button>
                    <button class="btn btn-primary" type="button" id="btn-kirim">Kirim Pendaftaran</button>
                </div>
            </form>

            {{-- Form upload dokumen (harus di LUAR form-pendaftaran: HTML tak mengizinkan form bertingkat) --}}
            @foreach ($jenisDokumen as $jenis)
                <form id="form-dok-{{ $jenis->value }}" method="POST" action="{{ url('/pendaftaran/dokumen') }}" enctype="multipart/form-data" class="d-none">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis->value }}">
                </form>
            @endforeach

            <form id="form-kirim" method="POST" action="{{ url('/pendaftaran/kirim') }}" class="d-none">
                @csrf
                <input type="hidden" name="pendaftar_id" value="{{ $p?->id }}">
            </form>
        </div>

        {{-- ===== CHECKLIST ===== --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm mb-3" id="checklist">
                <div class="card-header bg-white py-2"><h6 class="mb-0">Kemajuan</h6></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2" id="ck-data"><span class="badge bg-secondary">belum</span> Data Diri</li>
                        <li class="mb-2" id="ck-ortu"><span class="badge bg-secondary">belum</span> Orang Tua / Wali</li>
                        <li class="mb-2" id="ck-jurusan"><span class="badge bg-secondary">belum</span> Pilihan Jurusan</li>
                        <li id="ck-dok"><span class="badge bg-secondary">belum</span> Dokumen <span id="dok-count">0</span>/5</li>
                    </ul>
                    @if ($p?->nomor_pendaftaran)
                        <hr>
                        <div class="small text-muted">Status: <x-status-badge :status="$p->status" class="fs-7"/></div>
                    @endif
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body small text-muted">
                    Setelah dikirim, data hanya dapat diubah saat tahap perbaikan (jika digugur verifikasi).
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var doneDok = 0;
            var totalDok = {{ count($jenisDokumen) }};

            function setBadge(el, text, cls) {
                el.textContent = text;
                el.className = 'badge ' + cls;
            }

            function cekData() {
                var nama = document.querySelector('[name=nama_lengkap]').value.trim();
                var tgl = document.querySelector('[name=tanggal_lahir]').value;
                var jk = document.querySelector('[name=jenis_kelamin]').value;
                return nama && tgl && jk;
            }
            function cekOrtu() {
                return document.querySelector('[name=nama_orang_tua]').value.trim();
            }
            function cekJurusan() {
                return document.querySelector('[name=pilihan_1]').value !== '';
            }

            function updateChecklist() {
                var m = { 'ck-data': cekData(), 'ck-ortu': cekOrtu(), 'ck-jurusan': cekJurusan() };
                Object.keys(m).forEach(function (id) {
                    var b = document.querySelector('#' + id + ' .badge');
                    setBadge(b, m[id] ? '✓ selesai' : 'belum', m[id] ? 'bg-success' : 'bg-secondary');
                });
                var bd = document.querySelector('#ck-dok .badge');
                var teksDok = doneDok === totalDok ? '✓ selesai' : 'belum';
                setBadge(bd, teksDok, doneDok === totalDok ? 'bg-success' : 'bg-secondary');
                document.getElementById('dok-count').textContent = doneDok;
            }

            document.querySelectorAll('input[type=file][data-jenis]').forEach(function (input) {
                input.addEventListener('change', function () {
                    var jenis = this.dataset.jenis;
                    var errEl = document.getElementById('err-' + jenis);
                    var badge = document.getElementById('slot-' + jenis);
                    var form = document.getElementById('form-dok-' + jenis);
                    errEl.textContent = '';
                    if (!this.files.length) return;

                    setBadge(badge, 'mengunggah…', 'bg-warning text-dark');
                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    }).then(function (r) {
                        return r.json().then(function (j) { return { ok: r.ok, j: j }; });
                    }).then(function (res) {
                        if (res.ok && res.j.ok) {
                            setBadge(badge, '✓ Tersimpan', 'bg-success');
                            if (badge.dataset.dicek !== '1') { badge.dataset.dicek = '1'; doneDok++; }
                            updateChecklist();
                        } else {
                            setBadge(badge, 'Gagal', 'bg-danger');
                            errEl.textContent = res.j.error || 'Gagal mengunggah.';
                        }
                    }).catch(function () {
                        location.reload();
                    });
                });
            });

            // hitung ulang status slot yang sudah tersimpan (reload)
            document.querySelectorAll('[id^=slot-]').forEach(function (b) {
                if (b.classList.contains('bg-success')) { b.dataset.dicek = '1'; doneDok++; }
            });
            document.querySelectorAll('#seksi-data input, #seksi-data select').forEach(function (el) {
                el.addEventListener('input', updateChecklist);
                el.addEventListener('change', updateChecklist);
            });
            document.querySelectorAll('#seksi-ortu input').forEach(function (el) {
                el.addEventListener('input', updateChecklist);
            });
            document.querySelector('[name=pilihan_1]').addEventListener('change', updateChecklist);
            updateChecklist();

            // tombol kirim
            document.getElementById('btn-kirim').addEventListener('click', function () {
                var id = @json($p?->id);
                if (!id) {
                    alert('Simpan draf terlebih dahulu (tombol Simpan Draf), lalu unggah semua dokumen.');
                    return;
                }
                if (doneDok < totalDok) {
                    if (!confirm('Dokumen baru ' + doneDok + '/' + totalDok + '. Tetap kirim? Pendaftaran mungkin ditolak karena dokumen belum lengkap.')) return;
                }
                if (!confirm('Kirim pendaftaran? Setelah dikirim, data hanya dapat diubah saat tahap perbaikan.')) return;
                document.getElementById('form-kirim').submit();
            });
        })();
    </script>
@endsection
