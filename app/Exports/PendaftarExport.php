<?php

namespace App\Exports;

use App\Models\Pendaftar;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PendaftarExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private array $filter = []) {}

    public function title(): string
    {
        return 'Pendaftar PSB';
    }

    public function query()
    {
        $q = Pendaftar::query()
            ->with(['pilihan.jurusan', 'nilai', 'hasil.jurusan'])
            ->orderByDesc('id');

        if (! empty($this->filter['status'])) {
            $q->where('status', $this->filter['status']);
        }

        if (! empty($this->filter['jurusan'])) {
            $q->whereHas('pilihan', fn ($pq) => $pq->where('jurusan_id', $this->filter['jurusan']));
        }

        return $q;
    }

    public function headings(): array
    {
        return [
            'No. Pendaftaran', 'Nama Lengkap', 'NISN', 'Pilihan 1', 'Pilihan 2',
            'Total Nilai', 'Status Akhir', 'Jurusan Diterima',
        ];
    }

    public function map($p): array
    {
        $pl1 = $p->pilihan->firstWhere('prioritas', 1);
        $pl2 = $p->pilihan->firstWhere('prioritas', 2);

        return [
            $p->nomor_pendaftaran,
            $p->nama_lengkap,
            $p->nisn,
            $pl1?->jurusan?->kode,
            $pl2?->jurusan?->kode,
            $p->nilai?->total,
            $p->hasil?->status_akhir,
            $p->hasil?->jurusan?->nama,
        ];
    }
}
