@extends('layouts.admin')

@section('title', 'Audit Log')

@section('content')
    <h2 class="text-2xl font-bold tracking-tight text-slate-900 mb-5">Audit Log</h2>

    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3 w-44">Waktu</th>
                        <th class="px-5 py-3 w-44">Aksi</th>
                        <th class="px-5 py-3 w-40">Target</th>
                        <th class="px-5 py-3 w-40">Aktor</th>
                        <th class="px-5 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($log as $r)
                        <tr class="hover:bg-slate-50/60 align-top">
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $r->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200">{{ $r->aksi }}</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $r->target }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $r->pengguna?->name ?? 'Sistem' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500 break-all">{{ $r->detail ? json_encode($r->detail, JSON_UNESCAPED_UNICODE) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada log aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($log->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap gap-1.5">
                @if ($log->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50">«</span>
                @else
                    <a href="{{ $log->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">«</a>
                @endif
                @for ($i = max(1, $log->currentPage() - 2); $i <= min($log->lastPage(), $log->currentPage() + 2); $i++)
                    @if ($i === $log->currentPage())
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-brand-600">{{ $i }}</span>
                    @else
                        <a href="{{ $log->url($i) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">{{ $i }}</a>
                    @endif
                @endfor
                @if ($log->onLastPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50">»</span>
                @else
                    <a href="{{ $log->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">»</a>
                @endif
            </div>
        @endif
    </div>
@endsection
