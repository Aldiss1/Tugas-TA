@extends('layouts.admin')

@section('title', $j->exists ? 'Edit Jurusan' : 'Tambah Jurusan')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">{{ $j->exists ? 'Edit Jurusan: '.$j->kode : 'Tambah Jurusan' }}</h1>

    <form method="POST"
        action="{{ $j->exists ? route('admin.jurusan.update', $j) : route('admin.jurusan.store') }}">
        @csrf
        @if ($j->exists) @method('PUT') @endif

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <label>Kode <span class="text-danger">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode', $j->kode) }}" class="form-control" maxlength="10" required>
                    @error('kode') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Nama Program Keahlian <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $j->nama) }}" class="form-control" required>
                    @error('nama') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="form-control">{{ old('deskripsi', $j->deskripsi) }}</textarea>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label>Rombel <span class="text-danger">*</span></label>
                        <input type="number" name="rombel" value="{{ old('rombel', $j->rombel ?? 1) }}" class="form-control" min="1" max="20" required>
                        @error('rombel') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label>Kuota <span class="text-danger">*</span></label>
                        <input type="number" name="kuota" value="{{ old('kuota', $j->kuota?->kuota ?? 0) }}" class="form-control" min="0" required>
                        @error('kuota') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label>Cadangan <small class="text-muted">(kosong = 10% kuota)</small></label>
                        <input type="number" name="cadangan" value="{{ old('cadangan', $j->kuota?->cadangan) }}" class="form-control" min="0">
                        @error('cadangan') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="aktif" value="0">
                            <input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktif" @checked(old('aktif', $j->aktif ?? true))>
                            <label class="form-check-label" for="aktif">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.jurusan.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
