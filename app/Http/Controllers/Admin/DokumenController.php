<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisDokumen;
use App\Exceptions\DokumenInvalid;
use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\Psb\DokumenService;

class DokumenController extends Controller
{
    /**
     * Stream file dokumen pendaftar (preview, admin only).
     */
    public function show(Pendaftar $pendaftar, string $jenis, DokumenService $service)
    {
        $jenis = JenisDokumen::tryFrom($jenis);

        if (! $jenis) {
            abort(404);
        }

        $dok = $pendaftar->dokumen()->where('jenis', $jenis)->first();

        if (! $dok) {
            abort(404);
        }

        try {
            return $service->stream($dok);
        } catch (DokumenInvalid) {
            abort(404);
        }
    }
}
