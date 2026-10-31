<?php

namespace App\Services\Psb;

use App\Enums\StatusPendaftar;
use App\Exceptions\PengumumanBelumSiap;
use App\Models\AuditLog;
use App\Models\Hasil;
use App\Models\User;
use Illuminate\Support\Carbon;

class PengumumanService
{
    /**
     * Pengumuman dianggap terbit jika ada baris audit 'publish'.
     */
    public function sudahTerbit(): bool
    {
        return AuditLog::where('aksi', 'publish')->exists();
    }

    public function waktuTerbit(): ?Carbon
    {
        return AuditLog::where('aksi', 'publish')->orderBy('created_at')->first()?->created_at;
    }

    /**
     * Terbitkan pengumuman (idempoten).
     * Syarat: sudah ada hasil ranking. Penerbitan menerapkan status akhir
     * ke pendaftar (sebelumnya hasil disimpan diam-diam di tabel hasil).
     */
    public function terbitkan(User $by): void
    {
        if ($this->sudahTerbit()) {
            return;
        }

        if (Hasil::count() === 0) {
            throw PengumumanBelumSiap::make();
        }

        Hasil::with('pendaftar')->get()->each(function (Hasil $h): void {
            $h->pendaftar?->update([
                'status' => match ($h->status_akhir) {
                    'diterima' => StatusPendaftar::DITERIMA,
                    'cadangan' => StatusPendaftar::CADANGAN,
                    default => StatusPendaftar::TIDAK_DITERIMA,
                },
            ]);
        });

        app(AuditService::class)->catat($by, 'psb', 'publish', [
            'tahun_ajaran' => config('psb.tahun_ajaran'),
        ]);
    }
}
