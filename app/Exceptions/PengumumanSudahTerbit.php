<?php

namespace App\Exceptions;

use RuntimeException;

class PengumumanSudahTerbit extends RuntimeException
{
    public static function make(): static
    {
        return new static('Pengumuman sudah terbit — data terkunci.');
    }
}
