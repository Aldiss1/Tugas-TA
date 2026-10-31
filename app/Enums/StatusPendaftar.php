<?php

namespace App\Enums;

enum StatusPendaftar: string
{
    case DRAF = 'draf';
    case MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';
    case LULUS_VERIFIKASI = 'lulus_verifikasi';
    case GUGUR = 'gugur';
    case DITERIMA = 'diterima';
    case CADANGAN = 'cadangan';
    case TIDAK_DITERIMA = 'tidak_diterima';

    public function label(): string
    {
        return match ($this) {
            self::DRAF => 'Draf',
            self::MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::LULUS_VERIFIKASI => 'Lulus Verifikasi',
            self::GUGUR => 'Gugur',
            self::DITERIMA => 'Diterima',
            self::CADANGAN => 'Cadangan',
            self::TIDAK_DITERIMA => 'Tidak Diterima',
        };
    }
}
