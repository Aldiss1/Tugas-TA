@extends('layouts.public')

@section('title', 'Cek Status')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h3 class="mb-3">Cek Status Pendaftaran</h3>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted small">
                        Masukkan <strong>nomor pendaftaran</strong> dan <strong>tanggal lahir</strong> Anda.
                        Halaman ini juga berlaku untuk pendaftar yang datanya diinput manual oleh sekolah.
                    </p>
                    <form method="POST" action="{{ route('cek-status') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor Pendaftaran</label>
                                <input type="text" name="nomor" value="{{ old('nomor') }}" class="form-control"
                                    placeholder="PSB-2026-000001" required>
                                @error('nomor')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tgl_lahir" value="{{ old('tgl_lahir') }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary">Cek Status</button>
                        </div>
                    </form>
                </div>
            </div>

            @if (isset($pendaftar))
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5>{{ $pendaftar->nama_lengkap }}</h5>
                        <hr>
                        <table class="table table-sm align-middle">
                            <tr>
                                <th style="width: 220px">Nomor Pendaftaran</th>
                                <td>{{ $pendaftar->nomor_pendaftaran }}</td>
                            </tr>
                            <tr>
                                <th>Jurusan Pilihan</th>
                                <td>
                                    @forelse ($pendaftar->pilihan()->with('jurusan')->get() as $pl)
                                        <span class="badge bg-light text-dark border">
                                            Prioritas {{ $pl->prioritas }}: {{ $pl->jurusan->nama }}
                                        </span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-primary">{{ $pendaftar->status->label() }}</span>
                                    @if ($pendaftar->alasan_gugur)
                                        <div class="text-danger small mt-1">{{ $pendaftar->alasan_gugur }}</div>
                                    @endif
                                </td>
                            </tr>
                            @if ($pendaftar->hasil)
                                <tr>
                                    <th>Hasil Seleksi</th>
                                    <td>
                                        <strong>{{ ucfirst($pendaftar->hasil->status_akhir) }}</strong>
                                        @if ($pendaftar->hasil->jurusan)
                                            — {{ $pendaftar->hasil->jurusan->nama }} (peringkat {{ $pendaftar->hasil->perankingan }})
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
