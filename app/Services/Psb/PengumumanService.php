<?php

namespace App\Services\Psb;

use App\Models\AuditLog;
use App\Models\User;

class PengumumanService
{
    /**
     * Pengumuman dianggap terbit jika ada baris audit 'publish'.
     */
    public function sudahTerbit(): bool
    {
        return AuditLog::where('aksi', 'publish')->exists();
    }

    public function waktuTerbit(): ?\Illuminate\Support\Carbon
    {
        return AuditLog::where('aksi', 'publish')->orderBy('created_at')->first()?->created_at;
    }

    /**
     * Terbitkan pengumuman (idempoten).
     */
    public function terbitkan(User $by): void
    {
        if ($this->sudahTerbit()) {
            return;
        }

        app(AuditService::class)->catat($by, 'psb', 'publish', [
            'tahun_ajaran' => config('psb.tahun_ajaran'),
        ]);
    }
}
