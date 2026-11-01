@extends('layouts.portal')

@section('title', 'Akun Saya')

@section('content')
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Data akun --}}
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-6">
            <h3 class="font-bold text-slate-900 mb-4">Data Akun</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ auth()->user()->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ auth()->user()->email }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">NISN</dt>
                    <dd class="font-mono font-medium text-slate-900 text-right">{{ auth()->user()->nisn ?? '—' }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-slate-400">Email tidak dapat diubah — hubungi admin bila ada kesalahan.</p>
        </div>

        {{-- Ubah password --}}
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-6">
            <h3 class="font-bold text-slate-900 mb-4">Ubah Password</h3>
            <form method="POST" action="{{ route('akun.password') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="password_lama" class="block text-sm font-medium text-slate-700 mb-1">Password Lama</label>
                    <input id="password_lama" type="password" name="password_lama" required
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('password_lama') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
                    <input id="password" type="password" name="password" required
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Minimal 8 karakter">
                    @error('password') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Ulangi Password Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('password_confirmation') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition shadow-sm">
                    Simpan Password Baru
                </button>
                <p class="text-xs text-slate-400">Setelah password diubah, Anda akan diminta masuk kembali dengan password baru.</p>
            </form>
        </div>
    </div>
@endsection
