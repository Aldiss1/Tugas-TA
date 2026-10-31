<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kuota extends Model
{
    use HasFactory;

    protected $table = 'kuota';

    protected $fillable = [
        'jurusan_id', 'kuota', 'cadangan', 'terisi',
    ];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function sisa(): int
    {
        return max(0, $this->kuota - $this->terisi);
    }
}
