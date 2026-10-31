@extends('layouts.admin')

@section('title', 'Jurusan & Kuota')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Jurusan & Kuota</h1>
        <a href="{{ route('admin.jurusan.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Jurusan</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Kode</th><th>Nama</th><th>Rombel</th><th>Kapasitas</th><th>Kuota</th><th>Cadangan</th><th>Terisi</th><th>Status</th><th style="width:150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jurusan as $j)
                        <tr>
                            <td><strong>{{ $j->kode }}</strong></td>
                            <td>{{ $j->nama }}</td>
                            <td>{{ $j->rombel }}</td>
                            <td>{{ $j->kapasitas() }}</td>
                            <td>{{ $j->kuota->kuota ?? 0 }}</td>
                            <td>{{ $j->kuota->cadangan ?? 0 }}</td>
                            <td>{{ $j->kuota->terisi ?? 0 }}</td>
                            <td>
                                @if ($j->aktif)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-sm btn-warning" href="{{ route('admin.jurusan.edit', $j) }}"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.jurusan.destroy', $j) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus jurusan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
