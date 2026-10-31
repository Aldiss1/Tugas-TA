<?php

namespace App\Models;

use App\Enums\TahapJadwal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPsb extends Model
{
    use HasFactory;

    protected $table = 'jadwal_psb';

    protected $fillable = [
        'tahap', 'mulai', 'selesai',
    ];

    protected $casts = [
        'tahap' => TahapJadwal::class,
        'mulai' => 'datetime',
        'selesai' => 'datetime',
    ];
}
