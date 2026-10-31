@extends('layouts.portal')

@section('title', 'Status Pendaftaran')

@section('content')
    @if (! $p)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <h4>Belum Ada Pendaftaran</h4>
                <p class="text-muted">Isi formulir pendaftaran terlebih dahulu.</p>
                <a href="{{ route('pendaftaran') }}" class="btn btn-primary">Mulai Pendaftaran</a>
            </div>
        </div>
    @else
        {{-- Kartu utama --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="text-muted small">Halo, {{ auth()->user()->name }}</div>
                        <h2 class="mb-1">{{ $p->nomor_pendaftaran ?? '(belum terkirim)' }}</h2>
                        <div class="mb-2">
                            <x-status-badge :status="$p->status" class="fs-6"/>
                        </div>
                        <div class="small text-muted">
                            @forelse ($p->pilihan()->with('jurusan')->orderBy('prioritas')->get() as $pl)
                                <span class="badge bg-light text-dark border">P{{ $pl->prioritas }}: {{ $pl->jurusan->nama }}</span>
                            @empty
                                Jurusan belum dipilih.
                            @endforelse
                        </div>
                    </div>
                    @if ($bolehRevisi && $p->status->value === 'gugur')
                        <form method="POST" action="{{ url('/pendaftaran/revisi') }}">
                            @csrf
                            <input type="hidden" name="pendaftar_id" value="{{ $p->id }}">
                            <button class="btn btn-primary">Kirim Ulang Pendaftaran</button>
                        </form>
                    @elseif ($bolehRevisi)
                        <a href="{{ route('pendaftaran') }}" class="btn btn-outline-primary">Lanjutkan / Perbarui Draf</a>
                    @endif
                </div>
                @if ($p->alasan_gugur)
                    <div class="alert alert-danger py-2 mt-3 mb-0">
                        <strong>Alasan gugur:</strong> {{ $p->alasan_gugur }}
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Kelengkapan Dokumen</h5>
                        @php
                            $mapDok = $p->dokumen()->get()->keyBy(fn ($d) => $d->jenis->value);
                        @endphp
                        <ul class="list-group list-group-flush">
                            @foreach (\App\Enums\JenisDokumen::cases() as $jenis)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    {{ $jenis->label() }}
                                    @if (isset($mapDok[$jenis->value]) && $mapDok[$jenis->value]->diunggah_at)
                                        <span class="badge bg-success">✓ Tersimpan</span>
                                    @else
                                        <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Info</h5>
                        <table class="table table-sm">
                            <tr><th style="width:45%">Nama</th><td>{{ $p->nama_lengkap }}</td></tr>
                            <tr><th>NISN</th><td>{{ $p->nisn }}</td></tr>
                            <tr><th>Asal Sekolah</th><td>{{ $p->asal_sekolah }}</td></tr>
                            <tr><th>Dikirim</th><td>{{ $p->dikirim_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            @if ($p->hasil && $sudahTerbit)
                <div class="col-12">
                    <div class="card border-0 shadow-sm {{ $p->hasil->status_akhir === 'diterima' ? 'border-success' : '' }}">
                        <div class="card-body">
                            <h5 class="card-title">Hasil Seleksi</h5>
                            <p class="mb-1">
                                Status akhir:
                                <strong class="{{ $p->hasil->status_akhir === 'diterima' ? 'text-success' : 'text-danger' }}">
                                    {{ ucfirst(str_replace('_', ' ', $p->hasil->status_akhir)) }}
                                </strong>
                            </p>
                            @if ($p->hasil->jurusan)
                                <p class="mb-1">Jurusan diterima: <strong>{{ $p->hasil->jurusan->nama }}</strong> (peringkat {{ $p->hasil->perankingan }})</p>
                            @endif
                            @if ($p->hasil->status_akhir === 'diterima')
                                <a class="btn btn-success" href="{{ url('/surat/'.$p->nomor_pendaftaran) }}" target="_blank">Cetak Surat Keterangan Diterima</a>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif ($p->status->value === 'lulus_verifikasi' && ! $sudahTerbit)
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-muted">
                            Menunggu pengumuman — hasil seleksi akan
                            ditampilkan di halaman ini setelah pengumuman diterbitkan.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection
