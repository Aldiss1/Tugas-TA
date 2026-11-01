@extends('layouts.admin')

@section('title', 'Jadwal PSB')

@section('content')
    <h2 class="text-2xl font-bold tracking-tight text-slate-900 mb-5">Jadwal PSB {{ config('psb.tahun_ajaran') }}</h2>

    <form method="POST" action="{{ route('admin.jadwal.update') }}">
        @csrf
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($tahap as $t)
                @php $row = $jadwal[$t->value] ?? null; @endphp
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl {{ $row && $row->mulai->lessThanOrEqualTo(now()) && $row->selesai->greaterThanOrEqualTo(now()) ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                            <x-icon-calendar class="h-5 w-5" />
                        </span>
                        <div>
                            <div class="font-bold text-slate-900">{{ $t->label() }}</div>
                            @if ($row && $row->mulai->lessThanOrEqualTo(now()) && $row->selesai->greaterThanOrEqualTo(now()))
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Berlangsung
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Mulai</label>
                            <input type="datetime-local" name="mulai[{{ $t->value }}]" required
                                value="{{ old('mulai.'.$t->value, $row?->mulai?->format('Y-m-d\TH:i')) }}"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('mulai.'.$t->value) <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Selesai</label>
                            <input type="datetime-local" name="selesai[{{ $t->value }}]" required
                                value="{{ old('selesai.'.$t->value, $row?->selesai?->format('Y-m-d\TH:i')) }}"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('selesai.'.$t->value) <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition">
                <x-icon-check class="h-4 w-4" /> Simpan Jadwal
            </button>
        </div>
    </form>
@endsection
