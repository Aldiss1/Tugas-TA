@extends('layouts.admin')

@section('title', 'Pendaftar')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Pendaftar</h1>
        <a href="{{ route('admin.pendaftar.create') }}" class="btn btn-primary btn-sm mb-2"><i class="fas fa-plus"></i> Input Manual</a>
        <form method="GET" action="{{ url('/admin/pendaftar') }}" class="form-inline d-flex flex-wrap gap-2">
            <select name="status" class="form-control form-control-sm">
                <option value="">Semua status</option>
                @foreach ($status as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <select name="jurusan" class="form-control form-control-sm">
                <option value="">Semua jurusan</option>
                @foreach ($jurusan as $j)
                    <option value="{{ $j->id }}" @selected((string) request('jurusan') === (string) $j->id)>{{ $j->kode }}</option>
                @endforeach
            </select>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama/NISN/no. pendaftaran">
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>P1</th><th>P2</th>
                        <th>Dokumen</th><th>Sumber</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendaftar as $p)
                        @php
                            $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value);
                            $pl1 = $p->pilihan->firstWhere('prioritas', 1);
                            $pl2 = $p->pilihan->firstWhere('prioritas', 2);
                        @endphp
                        <tr>
                            <td>{{ $p->nomor_pendaftaran ?? '—' }}</td>
                            <td>{{ $p->nama_lengkap }}</td>
                            <td>{{ $p->nisn }}</td>
                            <td>{{ $pl1?->jurusan?->kode ?? '—' }}</td>
                            <td>{{ $pl2?->jurusan?->kode ?? '—' }}</td>
                            <td>
                                @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                    <span class="badge badge-{{ (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at) ? 'success' : 'secondary' }}"
                                        title="{{ $jenis->label() }}">{{ strtoupper(substr($jenis->value, 0, 2)) }}</span>
                                @endforeach
                            </td>
                            <td>{{ ucfirst($p->sumber) }}</td>
                            <td><span class="badge badge-primary">{{ $p->status->label() }}</span></td>
                            <td>
                                <a class="btn btn-sm btn-info" href="{{ url('/admin/pendaftar/'.$p->id) }}"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada pendaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $pendaftar->links() }}
        </div>
    </div>
@endsection
