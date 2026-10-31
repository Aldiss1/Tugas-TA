<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NilaiSeleksi extends Model
{
    use HasFactory;

    protected $table = 'nilai_seleksi';

    protected $fillable = [
        'pendaftar_id', 'nilai_rapor', 'nilai_prestasi', 'nilai_kunjungan', 'total', 'diinput_at',
    ];

    protected $casts = [
        'diinput_at' => 'datetime',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(NilaiSeleksiRiwayat::class);
    }
}
