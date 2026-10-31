<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use Illuminate\View\View;

class SuratController extends Controller
{
    /**
     * Surat keterangan diterima (print/PDF via browser), milik sendiri saja.
     */
    public function show(string $nomor, Pendaftar $p = null): View
    {
        // dipanggil dengan parameter nomor; pendaftar dicari manual untuk cek kepemilikan
        abort_unless(auth()->check(), 401);

        $p = Pendaftar::where('nomor_pendaftaran', $nomor)->first();
        abort_if(! $p, 404);
        abort_if(! $p->pengguna_id || $p->pengguna_id !== auth()->id(), 403);
        abort_if($p->hasil?->status_akhir !== 'diterima', 404);

        $p->load(['hasil.jurusan']);

        return view('portal.surat', [
            'p' => $p,
            'kop' => [
                'nama' => config('psb.nama_sekolah'),
                'alamat' => config('psb.alamat'),
                'tahun_ajaran' => config('psb.tahun_ajaran'),
            ],
        ]);
    }
}
