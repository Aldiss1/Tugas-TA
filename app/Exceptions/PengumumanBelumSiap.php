<?php

namespace App\Exceptions;

use RuntimeException;

class PengumumanBelumSiap extends RuntimeException
{
    public static function make(): static
    {
        return new static('Belum ada hasil ranking — proses ranking terlebih dahulu.');
    }
}
