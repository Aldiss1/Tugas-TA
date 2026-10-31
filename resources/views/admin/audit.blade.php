@extends('layouts.admin')

@section('title', 'Audit Log')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Audit Log</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr><th style="width:160px">Waktu</th><th>Aksi</th><th>Target</th><th>Aktor</th><th>Detail</th></tr>
                </thead>
                <tbody>
                    @forelse ($log as $r)
                        <tr>
                            <td>{{ $r->created_at?->format('d M Y H:i:s') }}</td>
                            <td><span class="badge badge-primary">{{ $r->aksi }}</span></td>
                            <td>{{ $r->target }}</td>
                            <td>{{ $r->pengguna?->name ?? 'Sistem' }}</td>
                            <td class="small text-muted">{{ $r->detail ? json_encode($r->detail) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $log->links() }}
        </div>
    </div>
@endsection
