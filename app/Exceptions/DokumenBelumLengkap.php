<?php

namespace App\Exceptions;

use RuntimeException;

class DokumenBelumLengkap extends RuntimeException
{
    public function __construct(public array $kurang = [])
    {
        parent::__construct('Dokumen persyaratan belum lengkap.'.($kurang ? ' Kurang: '.implode(', ', $kurang) : ''));
    }
}
