<?php

namespace App\Services\Psb;

use App\Enums\JenisDokumen;
use App\Exceptions\DokumenInvalid;
use App\Models\Dokumen;
use App\Models\Pendaftar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DokumenService
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    /**
     * MIME yang diizinkan => ekstensi.
     */
    private const MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Simpan (atau ganti, jika sudah ada) dokumen untuk pendaftar.
     */
    public function simpan(Pendaftar $p, JenisDokumen|string $jenis, UploadedFile $file): Dokumen
    {
        return $this->tulis($p, $jenis, $file);
    }

    public function ganti(Pendaftar $p, JenisDokumen|string $jenis, UploadedFile $file): Dokumen
    {
        return $this->tulis($p, $jenis, $file);
    }

    private function tulis(Pendaftar $p, JenisDokumen|string $jenis, UploadedFile $file): Dokumen
    {
        $jenis = $jenis instanceof JenisDokumen ? $jenis : JenisDokumen::from($jenis);
        $this->validasi($file);

        $mime = $file->getMimeType();
        $ext = self::MIME[$mime];

        $dok = $p->dokumen()->firstOrNew(['jenis' => $jenis]);
        $this->hapusFileLama($dok);

        $nama = $jenis->value.'_'.$Str::uuid().'.'.$ext;

        $file->storeAs('dokumen/'.$p->id, $nama, 'local');

        $dok->path = 'dokumen/'.$p->id.'/'.$nama;
        $dok->tipe_file = $mime;
        $dok->ukuran_bytes = $file->getSize();
        $dok->diunggah_at = now();
        $dok->save();

        return $dok;
    }

    private function validasi(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw DokumenInvalid::ukuran();
        }

        $mime = $file->getMimeType();
        if (! isset(self::MIME[$mime])) {
            throw DokumenInvalid::tipe();
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, [self::MIME[$mime]], true)) {
            throw DokumenInvalid::tipe();
        }
    }

    private function hapusFileLama(?Dokumen $dok): void
    {
        if ($dok && $dok->exists && $dok->path) {
            Storage::disk('local')->delete($dok->path);
        }
    }

    /**
     * Stream file dokumen (untuk preview admin). Lempar DokumenInvalid bila tidak ada.
     */
    public function stream(Dokumen $dok)
    {
        if (! $dok->path || ! Storage::disk('local')->exists($dok->path)) {
            throw DokumenInvalid::tipe();
        }

        return Storage::disk('local')->response($dok->path);
    }
}
