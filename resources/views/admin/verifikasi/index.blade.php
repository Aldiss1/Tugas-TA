@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Verifikasi Dokumen</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>Jurusan</th><th>Dokumen</th><th style="width:220px">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendaftar as $p)
                        @php $mapDok = $p->dokumen->keyBy(fn ($d) => $d->jenis->value); @endphp
                        <tr>
                            <td>{{ $p->nomor_pendaftaran ?? '—' }}</td>
                            <td>{{ $p->nama_lengkap }}</td>
                            <td>{{ $p->nisn }}</td>
                            <td>{{ $p->pilihan->first()?->jurusan?->kode ?? '—' }}</td>
                            <td>
                                @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                    <span class="badge badge-{{ (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at) ? 'success' : 'secondary' }}"
                                        title="{{ $jenis->label() }}">{{ strtoupper(substr($jenis->value, 0, 2)) }}</span>
                                @endforeach
                            </td>
                            <td>
                                <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}" class="d-inline"
                                    onsubmit="return confirm('Setujui pendaftar ini?')">
                                    @csrf
                                    <input type="hidden" name="aksi" value="lulus">
                                    <button class="btn btn-sm btn-success">Lulus</button>
                                </form>
                                <form method="POST" action="{{ url('/admin/verifikasi/'.$p->id.'/proses') }}" class="d-inline"
                                    onsubmit="var a=prompt('Alasan penguguran (wajib):'); if(!a) return false; this.alasan.value=a; return true;">
                                    @csrf
                                    <input type="hidden" name="aksi" value="gugur">
                                    <input type="hidden" name="alasan" value="">
                                    <button class="btn btn-sm btn-danger">Gugur</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Tidak ada pendaftar dalam antrian verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $pendaftar->links() }}
        </div>
    </div>
@endsection
