<?php

namespace App\Enums;

enum JenisDokumen: string
{
    case KTP = 'ktp';
    case KK = 'kk';
    case IJAZAH = 'ijazah';
    case RAPOR = 'rapor';
    case FOTO = 'foto';

    public function label(): string
    {
        return match ($this) {
            self::KTP => 'KTP',
            self::KK => 'Kartu Keluarga',
            self::IJAZAH => 'Ijazah / Surat Kelulusan',
            self::RAPOR => 'Rapor Semester Terakhir',
            self::FOTO => 'Foto 3x4',
        };
    }
}
