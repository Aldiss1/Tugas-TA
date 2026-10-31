@extends('layouts.admin')

@section('title', 'Input Pendaftar Manual')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Input Pendaftar Manual</h1>
        <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <form method="POST" action="{{ route('admin.pendaftar.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">NISN <span class="text-danger">*</span></label>
                        <input type="text" name="nisn" value="{{ old('nisn') }}" class="form-control" maxlength="10" required>
                        @error('nisn') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" class="form-control" required>
                        @error('nama_lengkap') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">—</option>
                            <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Agama</label>
                        <input type="text" name="agama" value="{{ old('agama') }}" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Asal Sekolah (SMP/MTs)</label>
                        <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah') }}" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" rows="2" class="form-control">{{ old('alamat') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIK</label>
                        <input type="text" name="nik" value="{{ old('nik') }}" class="form-control" maxlength="20">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Orang Tua / Wali</label>
                        <input type="text" name="nama_orang_tua" value="{{ old('nama_orang_tua') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="pekerjaan_orang_tua" value="{{ old('pekerjaan_orang_tua') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon_orang_tua" value="{{ old('telepon_orang_tua') }}" class="form-control" maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pilihan Jurusan 1 <span class="text-danger">*</span></label>
                        <select name="pilihan_1" class="form-select" required>
                            <option value="">— pilih —</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" @selected(old('pilihan_1') == $j->id)>{{ $j->nama }} ({{ $j->kode }})</option>
                            @endforeach
                        </select>
                        @error('pilihan_1') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pilihan Jurusan 2 (opsional)</label>
                        <select name="pilihan_2" class="form-select">
                            <option value="">— tidak ada —</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" @selected(old('pilihan_2') == $j->id)>{{ $j->nama }} ({{ $j->kode }})</option>
                            @endforeach
                        </select>
                        @error('pilihan_2') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Dokumen (opsional — PDF/JPG/PNG maks 2 MB)</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($jenisDokumen as $jenis)
                        <div class="col-md-6">
                            <label class="form-label small">{{ $jenis->label() }}</label>
                            <input type="file" name="dokumen_{{ $jenis->value }}" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            @error('dokumen_'.$jenis->value) <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Simpan Pendaftar</button>
    </form>
@endsection
