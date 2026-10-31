<?php

namespace App\Exceptions;

use RuntimeException;

class DokumenInvalid extends RuntimeException
{
    public static function ukuran(): static
    {
        return new static('Ukuran file maksimal 2 MB.');
    }

    public static function tipe(): static
    {
        return new static('Tipe file harus PDF, JPG, atau PNG.');
    }
}
