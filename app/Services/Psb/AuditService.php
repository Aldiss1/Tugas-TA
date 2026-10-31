<?php

namespace App\Services\Psb;

use App\Models\AuditLog;
use App\Models\User;

class AuditService
{
    /**
     * Catat aktivitas penting (aktor, target, aksi, detail).
     */
    public function catat(?User $by, string $target, string $aksi, array $detail = []): AuditLog
    {
        return AuditLog::create([
            'pengguna_id' => $by?->id,
            'target' => $target,
            'aksi' => $aksi,
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }
}
