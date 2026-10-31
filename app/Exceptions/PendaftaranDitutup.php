<?php

namespace App\Exceptions;

use RuntimeException;

class PendaftaranDitutup extends RuntimeException
{
    public static function make(): static
    {
        return new static('Periode pendaftaran sudah ditutup.');
    }
}
