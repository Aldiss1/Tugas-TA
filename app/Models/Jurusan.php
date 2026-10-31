<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Jurusan extends Model
{
    use HasFactory;

    protected $table = 'jurusan';

    protected $fillable = [
        'kode', 'nama', 'deskripsi', 'rombel', 'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function kuota(): HasOne
    {
        return $this->hasOne(Kuota::class);
    }

    public function pendaftarPilihan(): HasMany
    {
        return $this->hasMany(PendaftarPilihan::class);
    }

    public function kapasitas(): int
    {
        return $this->rombel * (int) config('psb.siswa_per_rombel');
    }
}
