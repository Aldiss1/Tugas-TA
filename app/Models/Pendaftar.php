<?php

namespace App\Models;

use App\Enums\StatusPendaftar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendaftar extends Model
{
    use HasFactory;

    protected $table = 'pendaftar';

    protected $fillable = [
        'nomor_pendaftaran', 'pengguna_id', 'sumber', 'nisn',
        'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
        'agama', 'asal_sekolah', 'alamat', 'nik',
        'nama_orang_tua', 'pekerjaan_orang_tua', 'telepon_orang_tua',
        'status', 'alasan_gugur', 'dikirim_at',
    ];

    protected $casts = [
        'status' => StatusPendaftar::class,
        'tanggal_lahir' => 'date',
        'dikirim_at' => 'datetime',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pilihan(): HasMany
    {
        return $this->hasMany(PendaftarPilihan::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    public function nilai(): HasOne
    {
        return $this->hasOne(NilaiSeleksi::class);
    }

    public function hasil(): HasOne
    {
        return $this->hasOne(Hasil::class);
    }
}
