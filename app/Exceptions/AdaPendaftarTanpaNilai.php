<?php

namespace App\Exceptions;

use RuntimeException;

class AdaPendaftarTanpaNilai extends RuntimeException
{
    public function __construct(public array $ids = [])
    {
        parent::__construct('Masih ada pendaftar lulus verifikasi tanpa nilai.');
    }
}
