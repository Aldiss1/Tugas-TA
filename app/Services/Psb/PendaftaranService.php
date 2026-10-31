<?php

namespace App\Services\Psb;

use App\Enums\JenisDokumen;
use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Exceptions\DokumenBelumLengkap;
use App\Exceptions\NISNDuplikat;
use App\Exceptions\PendaftaranDitutup;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PendaftaranService
{
    /**
     * Buat atau perbarui draf pendaftaran untuk user.
     */
    public function simpanDraf(User $user, array $data): Pendaftar
    {
        $p = Pendaftar::firstOrNew(['pengguna_id' => $user->id]);
        $p->fill($this->satuData($data, $user));

        try {
            $p->save();
        } catch (QueryException $e) {
            // NISN dobel yang lolos validasi form (mis. race dengan input manual)
            if (str_contains($e->getMessage(), 'nisn')) {
                throw new NISNDuplikat((string) ($data['nisn'] ?? $user->nisn));
            }
            throw $e;
        }

        $this->simpanPilihan($p, (int) ($data['pilihan_1'] ?? 0), (int) ($data['pilihan_2'] ?? 0));

        return $p;
    }

    /**
     * Kirim draf: cek periode, keunikan NISN, kelengkapan dokumen,
     * lalu terbitkan nomor pendaftaran dan ubah status.
     */
    public function kirim(Pendaftar $p): Pendaftar
    {
        if (! app(JadwalService::class)->isBuka(TahapJadwal::PENDAFTARAN)) {
            throw PendaftaranDitutup::make();
        }

        $this->pastikanNISNUnik($p);
        $this->pastikanDokumenLengkap($p);

        return DB::transaction(function () use ($p) {
            for ($coba = 0; $coba < 3; $coba++) {
                try {
                    if (! $p->nomor_pendaftaran || $coba > 0) {
                        $p->nomor_pendaftaran = app(NomorPendaftaran::class)->generate();
                    }
                    $p->status = StatusPendaftar::MENUNGGU_VERIFIKASI;
                    $p->dikirim_at = now();
                    $p->save();

                    return $p;
                } catch (QueryException $e) {
                    // Tabrakan nomor pendaftaran (race concurrent) — coba nomor berikutnya
                    if ($coba < 2 && str_contains($e->getMessage(), 'nomor_pendaftaran')) {
                        continue;
                    }
                    throw $e;
                }
            }

            throw new \RuntimeException('Gagal menerbitkan nomor pendaftaran setelah 3 percobaan.');
        });
    }

    /**
     * Kirim ulang setelah perbaikan (status GUGUR + tahap perbaikan buka).
     */
    public function kirimUlang(Pendaftar $p, ?User $by = null): Pendaftar
    {
        $this->pastikanDokumenLengkap($p);

        $p->status = StatusPendaftar::MENUNGGU_VERIFIKASI;
        $p->alasan_gugur = null;
        $p->save();

        app(AuditService::class)->catat($by, 'pendaftar:'.$p->id, 'kirim_ulang');

        return $p;
    }

    /**
     * Pendaftar boleh mengubah datanya hanya saat:
     * draf + periode pendaftaran buka, ATAU gugur + tahap perbaikan buka.
     */
    public function bolehRevisi(?Pendaftar $p): bool
    {
        if (! $p) {
            return false;
        }

        $jadwal = app(JadwalService::class);

        if ($p->status === StatusPendaftar::DRAF) {
            return $jadwal->isBuka(TahapJadwal::PENDAFTARAN);
        }

        if ($p->status === StatusPendaftar::GUGUR) {
            return $jadwal->isBuka(TahapJadwal::PERBAIKAN);
        }

        return false;
    }

    private function pastikanDokumenLengkap(Pendaftar $p): void
    {
        $tersedia = $p->dokumen()
            ->whereNotNull('diunggah_at')
            ->pluck('jenis')
            ->map(fn ($j) => $j instanceof JenisDokumen ? $j->value : (string) $j)
            ->all();

        $kurang = [];
        foreach (config('psb.jenis_dokumen') as $jenis) {
            if (! in_array($jenis, $tersedia, true)) {
                $kurang[] = JenisDokumen::from($jenis)->label();
            }
        }

        if ($kurang) {
            throw new DokumenBelumLengkap($kurang);
        }
    }

    private function pastikanNISNUnik(Pendaftar $p): void
    {
        if (! $p->nisn) {
            return;
        }

        $diPendaftar = Pendaftar::where('nisn', $p->nisn)->where('id', '!=', $p->id)->exists();
        $diUser = User::where('nisn', $p->nisn)
            ->when($p->pengguna_id, fn ($q) => $q->where('id', '!=', $p->pengguna_id))
            ->exists();

        if ($diPendaftar || $diUser) {
            throw new NISNDuplikat($p->nisn);
        }
    }

    private function simpanPilihan(Pendaftar $p, int $pilihan1, int $pilihan2): void
    {
        if ($pilihan1 > 0) {
            PendaftarPilihan::updateOrCreate(
                ['pendaftar_id' => $p->id, 'prioritas' => 1],
                ['jurusan_id' => $pilihan1]
            );
        }

        if ($pilihan2 > 0 && $pilihan2 !== $pilihan1) {
            PendaftarPilihan::updateOrCreate(
                ['pendaftar_id' => $p->id, 'prioritas' => 2],
                ['jurusan_id' => $pilihan2]
            );
        } elseif ($pilihan2 === 0) {
            $p->pilihan()->where('prioritas', 2)->delete();
        }
    }

    private function satuData(array $data, User $user): array
    {
        return collect([
            'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
            'agama', 'asal_sekolah', 'alamat', 'nik',
            'nama_orang_tua', 'pekerjaan_orang_tua', 'telepon_orang_tua',
        ])
            ->filter(fn ($f) => array_key_exists($f, $data))
            ->mapWithKeys(fn ($f) => [$f => $data[$f]])
            ->merge([
                'nisn' => $data['nisn'] ?? $user->nisn,
            ])
            ->all();
    }
}
