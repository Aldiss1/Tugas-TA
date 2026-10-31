<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiSeleksiRiwayat extends Model
{
    use HasFactory;

    protected $table = 'nilai_seleksi_riwayat';

    public $timestamps = false;

    protected $fillable = [
        'nilai_seleksi_id', 'snapshot', 'diubah_oleh', 'changed_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'changed_at' => 'datetime',
    ];

    public function nilaiSeleksi(): BelongsTo
    {
        return $this->belongsTo(NilaiSeleksi::class);
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
