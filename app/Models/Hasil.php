<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hasil extends Model
{
    use HasFactory;

    protected $table = 'hasil';

    public $timestamps = false;

    protected $fillable = [
        'pendaftar_id', 'jurusan_id', 'perankingan', 'status_akhir', 'via_pilihan', 'diproses_at',
    ];

    protected $casts = [
        'diproses_at' => 'datetime',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }
}
