@extends('layouts.admin')

@section('title', $j->exists ? 'Edit Jurusan' : 'Tambah Jurusan')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.jurusan.index') }}"
                class="inline-flex items-center justify-center h-9 w-9 rounded-lg ring-1 ring-inset ring-slate-300 text-slate-500 hover:bg-slate-100 transition">
                <x-icon-arrow-left class="h-4 w-4" />
            </a>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $j->exists ? 'Edit Jurusan: '.$j->kode : 'Tambah Jurusan' }}</h2>
        </div>
    </div>

    <form method="POST"
        action="{{ $j->exists ? route('admin.jurusan.update', $j) : route('admin.jurusan.store') }}"
        class="max-w-3xl">
        @csrf
        @if ($j->exists) @method('PUT') @endif

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6 space-y-5">
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <x-input label="Kode" name="kode" required value="{{ old('kode', $j->kode) }}" placeholder="cth: TKJ" />
                </div>
                <div class="sm:col-span-2">
                    <x-input label="Nama Program Keahlian" name="nama" required value="{{ old('nama', $j->nama) }}" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="3"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('deskripsi', $j->deskripsi) }}</textarea>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <x-input label="Jumlah Rombel" name="rombel" type="number" required value="{{ old('rombel', $j->rombel ?? 1) }}" />
                <x-input label="Kuota" name="kuota" type="number" required value="{{ old('kuota', $j->kuota?->kuota ?? 0) }}" />
                <x-input label="Cadangan (kosong = 10% kuota)" name="cadangan" type="number" value="{{ old('cadangan', $j->kuota?->cadangan) }}" />
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input id="aktif-check" type="checkbox" name="aktif" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4"
                    @checked(old('aktif', $j->aktif ?? true))>
                <label for="aktif-check" class="text-sm font-medium text-slate-700">Jurusan aktif untuk pendaftaran</label>
            </div>
        </div>

        <div class="mt-5 flex items-center gap-3">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition">
                <x-icon-check class="h-4 w-4" /> Simpan
            </button>
            <a href="{{ route('admin.jurusan.index') }}"
                class="inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-100 transition">
                Batal
            </a>
        </div>
    </form>
@endsection
