@extends('layouts.admin')

@section('title', 'Jadwal PSB')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Jadwal PSB {{ config('psb.tahun_ajaran') }}</h1>

    <form method="POST" action="{{ route('admin.jadwal.update') }}">
        @csrf
        <div class="card shadow mb-4">
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr><th>Tahap</th><th>Mulai</th><th>Selesai</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($tahap as $t)
                            @php $row = $jadwal[$t->value] ?? null; @endphp
                            <tr>
                                <td><strong>{{ $t->label() }}</strong></td>
                                <td>
                                    <input type="datetime-local" name="mulai[{{ $t->value }}]"
                                        value="{{ old('mulai.'.$t->value, $row?->mulai?->format('Y-m-d\TH:i')) }}"
                                        class="form-control" required>
                                    @error('mulai.'.$t->value) <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <input type="datetime-local" name="selesai[{{ $t->value }}]"
                                        value="{{ old('selesai.'.$t->value, $row?->selesai?->format('Y-m-d\TH:i')) }}"
                                        class="form-control" required>
                                    @error('selesai.'.$t->value) <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Simpan Jadwal</button>
            </div>
        </div>
    </form>
@endsection
