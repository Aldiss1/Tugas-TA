@extends('layouts.portal')

@section('title', 'Pendaftaran')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Formulir Pendaftaran</h3>
        @if ($p?->nomor_pendaftaran)
            <span class="badge bg-primary fs-6">No. Pendaftaran: {{ $p->nomor_pendaftaran }}</span>
        @endif
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1" type="button">1. Data Diri</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2" type="button">2. Orang Tua / Wali</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3" type="button">3. Pilihan Jurusan & Dokumen</button></li>
    </ul>

    <div class="tab-content card card-body border-0 shadow-sm p-4">
        <form method="POST" action="{{ route('pendaftaran') }}" id="form-pendaftaran">
            @csrf

            {{-- Tab 1: Data Diri --}}
            <div class="tab-pane fade show active" id="tab1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $p?->nama_lengkap) }}" class="form-control" required>
                        @error('nama_lengkap') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $p?->tempat_lahir) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $p?->tanggal_lahir?->format('Y-m-d')) }}" class="form-control" required>
                        @error('tanggal_lahir') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">—</option>
                            <option value="L" @selected(old('jenis_kelamin', $p?->jenis_kelamin) === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('jenis_kelamin', $p?->jenis_kelamin) === 'P')>Perempuan</option>
                        </select>
                        @error('jenis_kelamin') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agama</label>
                        <input type="text" name="agama" value="{{ old('agama', $p?->agama) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Asal Sekolah (SMP/MTs)</label>
                        <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah', $p?->asal_sekolah) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIK</label>
                        <input type="text" name="nik" value="{{ old('nik', $p?->nik) }}" class="form-control" maxlength="20">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" rows="2" class="form-control">{{ old('alamat', $p?->alamat) }}</textarea>
                    </div>
                </div>
                <hr>
                <p class="small text-muted mb-0">NISN: <strong>{{ auth()->user()->nisn }}</strong> (dari akun, tidak dapat diubah)</p>
            </div>

            {{-- Tab 2: Orang Tua --}}
            <div class="tab-pane fade" id="tab2">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Orang Tua / Wali</label>
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

            {{-- Tab 3: Pilihan & Dokumen --}}
            <div class="tab-pane fade" id="tab3">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Pilihan Jurusan 1 (wajib) <span class="text-danger">*</span></label>
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
                        <label class="form-label">Pilihan Jurusan 2 (opsional)</label>
                        <select name="pilihan_2" class="form-select">
                            <option value="">— tidak ada —</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" @selected((int) old('pilihan_2', $p?->pilihan()->where('prioritas', 2)->first()?->jurusan_id) === $j->id)>
                                    {{ $j->nama }} — sisa {{ $j->kuota->sisa() ?? 0 }}
                                </option>
                            @endforeach
                        </select>
                        @error('pilihan_2') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h6 class="mb-3">Dokumen Persyaratan (PDF/JPG/PNG, maks. 2 MB)</h6>
                <div class="row g-3">
                    @php
                        $mapDok = $p?->dokumen()->get()->keyBy(fn ($d) => $d->jenis->value);
                    @endphp
                    @foreach ($jenisDokumen as $jenis)
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-semibold">{{ $jenis->label() }}</span>
                                        @if ($mapDok && isset($mapDok[$jenis->value]))
                                            <span class="badge bg-success">Tersimpan</span>
                                        @else
                                            <span class="badge bg-secondary">Belum</span>
                                        @endif
                                    </div>
                                    <input type="file"
                                        form="form-{{ $jenis->value }}"
                                        name="file"
                                        class="form-control form-control-sm"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                    @error("dokumen.{$jenis->value}.file") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <form id="form-{{ $jenis->value }}" method="POST" action="{{ url('/pendaftaran/dokumen') }}" enctype="multipart/form-data" class="d-none">
                            @csrf
                            <input type="hidden" name="jenis" value="{{ $jenis->value }}">
                        </form>
                    @endforeach
                </div>
                <p class="text-danger small" id="err-file">@error('file') {{ $message }} @enderror</p>
            </div>

            <hr>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary" type="submit" form="form-pendaftaran">Simpan Draf</button>
                <button class="btn btn-primary" type="button" onclick="kirimPendaftaran()">Kirim Pendaftaran</button>
            </div>
        </form>

        <form id="form-kirim" method="POST" action="{{ url('/pendaftaran/kirim') }}" class="d-none">
            @csrf
            <input type="hidden" name="pendaftar_id" value="{{ $p?->id }}">
        </form>
    </div>

    <script>
        // Upload per dokumen: tampilkan error di panel umum, lalu reload
        @foreach ($jenisDokumen as $jenis)
            (function () {
                const input = document.querySelector('input[name=file][form="form-{{ $jenis->value }}"]');
                const form = document.getElementById('form-{{ $jenis->value }}');
                if (!input || !form) return;
                input.addEventListener('change', function () {
                    if (this.files.length === 0) return;
                    const fd = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    }).then(r => r.ok ? location.reload() : location.reload());
                });
            })();
        @endforeach

        function kirimPendaftaran() {
            const id = @json($p?->id);
            if (!id) {
                alert('Isi data diri dan simpan draf terlebih dahulu, lalu unggah semua dokumen.');
                return;
            }
            if (!confirm('Kirim pendaftaran? Setelah dikirim, data hanya bisa diubah saat tahap perbaikan.')) return;
            document.getElementById('form-kirim').submit();
        }
    </script>
@endsection
