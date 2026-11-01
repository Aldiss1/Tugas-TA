@extends('layouts.admin')

@section('title', 'Input Pendaftar Manual')

@section('content')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pendaftar.index') }}"
            class="inline-flex items-center justify-center h-9 w-9 rounded-lg ring-1 ring-inset ring-slate-300 text-slate-500 hover:bg-slate-100 transition">
            <x-icon-arrow-left class="h-4 w-4" />
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Input Pendaftar Manual</h2>
    </div>

    <form method="POST" action="{{ route('admin.pendaftar.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
            <h3 class="font-bold text-slate-900 mb-4">Data Pendaftar</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-input label="NISN *" name="nisn" required value="{{ old('nisn') }}" placeholder="10 digit" />
                <x-input label="Nama Lengkap *" name="nama_lengkap" required value="{{ old('nama_lengkap') }}" />
                <x-input label="Tempat Lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" />
                <x-input label="Tanggal Lahir *" name="tanggal_lahir" type="date" required value="{{ old('tanggal_lahir') }}" />
                <x-select label="Jenis Kelamin *" name="jenis_kelamin" required>
                    <option value="">—</option>
                    <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                </x-select>
                <x-input label="Agama" name="agama" value="{{ old('agama') }}" />
                <x-input label="Asal Sekolah (SMP/MTs)" name="asal_sekolah" value="{{ old('asal_sekolah') }}" />
                <x-input label="NIK" name="nik" value="{{ old('nik') }}" />
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                    <textarea name="alamat" rows="2"
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('alamat') }}</textarea>
                </div>
                <x-input label="Nama Orang Tua / Wali" name="nama_orang_tua" value="{{ old('nama_orang_tua') }}" />
                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Pekerjaan" name="pekerjaan_orang_tua" value="{{ old('pekerjaan_orang_tua') }}" />
                    <x-input label="Telepon" name="telepon_orang_tua" value="{{ old('telepon_orang_tua') }}" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
            <h3 class="font-bold text-slate-900 mb-4">Pilihan Jurusan</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-select label="Pilihan Jurusan 1 *" name="pilihan_1" required>
                    <option value="">— pilih —</option>
                    @foreach ($jurusan as $j)
                        <option value="{{ $j->id }}" @selected(old('pilihan_1') == $j->id)>{{ $j->nama }} ({{ $j->kode }})</option>
                    @endforeach
                </x-select>
                <div>
                    <x-select label="Pilihan Jurusan 2 (opsional)" name="pilihan_2">
                        <option value="">— tidak ada —</option>
                        @foreach ($jurusan as $j)
                            <option value="{{ $j->id }}" @selected(old('pilihan_2') == $j->id)>{{ $j->nama }} ({{ $j->kode }})</option>
                        @endforeach
                    </x-select>
                    <p class="mt-1 text-xs text-slate-400">Pilihan 2 harus berbeda dari Pilihan 1.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6">
            <h3 class="font-bold text-slate-900 mb-1">Dokumen <span class="text-xs font-medium text-slate-400">(opsional — PDF/JPG/PNG maks 2 MB)</span></h3>
            <div class="grid sm:grid-cols-2 gap-4 mt-4">
                @foreach ($jenisDokumen as $jenis)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ $jenis->label() }}</label>
                        <input type="file" name="dokumen_{{ $jenis->value }}"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 file:cursor-pointer"
                            accept=".pdf,.jpg,.jpeg,.png">
                        @error('dokumen_'.$jenis->value) <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition">
                <x-icon-check class="h-4 w-4" /> Simpan Pendaftar
            </button>
            <a href="{{ route('admin.pendaftar.index') }}"
                class="inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-100 transition">
                Kembali
            </a>
        </div>
    </form>
@endsection
