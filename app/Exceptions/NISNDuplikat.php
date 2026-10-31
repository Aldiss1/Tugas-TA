<?php

namespace App\Exceptions;

use RuntimeException;

class NISNDuplikat extends RuntimeException
{
    public function __construct(public string $nisn)
    {
        parent::__construct('NISN '.$nisn.' sudah terdaftar.');
    }
}
