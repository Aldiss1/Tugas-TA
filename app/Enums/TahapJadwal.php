<?php

namespace App\Enums;

enum TahapJadwal: string
{
    case PENDAFTARAN = 'pendaftaran';
    case VERIFIKASI = 'verifikasi';
    case PERBAIKAN = 'perbaikan';
    case SELEKSI = 'seleksi';
    case PENGUMUMAN = 'pengumuman';

    public function label(): string
    {
        return match ($this) {
            self::PENDAFTARAN => 'Pendaftaran',
            self::VERIFIKASI => 'Verifikasi Dokumen',
            self::PERBAIKAN => 'Perbaikan Dokumen',
            self::SELEKSI => 'Seleksi',
            self::PENGUMUMAN => 'Pengumuman',
        };
    }
}
