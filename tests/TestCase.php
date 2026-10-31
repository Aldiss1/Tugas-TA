<?php

namespace Tests;

use App\Enums\TahapJadwal;
use App\Models\JadwalPsb;
use App\Models\Pendaftar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Buka tahap pendaftaran (opsional override mulai/selesai).
     */
    protected function bukaPendaftaran(?string $mulai = null, ?string $selesai = null): void
    {
        JadwalPsb::where('tahap', 'pendaftaran')->update([
            'mulai' => $mulai ?? now()->subHour(),
            'selesai' => $selesai ?? now()->addDay(),
        ]);
    }

    protected function bukaTahap(TahapJadwal $tahap): void
    {
        JadwalPsb::where('tahap', $tahap)->update([
            'mulai' => now()->subHour(),
            'selesai' => now()->addDay(),
        ]);
    }

    protected function tutupTahap(TahapJadwal $tahap): void
    {
        JadwalPsb::where('tahap', $tahap)->update([
            'mulai' => now()->subDays(3),
            'selesai' => now()->subDay(),
        ]);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * Data form pendaftaran lengkap (pakai jurusan dari seeder).
     */
    protected function dataLengkap(?string $nisn = null): array
    {
        return [
            'nama_lengkap' => 'Budi Santoso',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2008-05-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'asal_sekolah' => 'SMPN 1 Singosari',
            'alamat' => 'Jl. Contoh No. 1, Malang',
            'nik' => '3201010101000001',
            'nama_orang_tua' => 'Bapak Budi',
            'pekerjaan_orang_tua' => 'Wiraswasta',
            'telepon_orang_tua' => '081234567890',
            'pilihan_1' => \App\Models\Jurusan::where('kode', 'TKJ')->value('id'),
            'pilihan_2' => \App\Models\Jurusan::where('kode', 'RPL')->value('id'),
        ] + ($nisn !== null ? ['nisn' => $nisn] : []);
    }

    /**
     * Pendaftar draf milik user (tanpa dokumen).
     */
    protected function buatPendaftarDraf(): Pendaftar
    {
        return Pendaftar::factory()->denganUser()->create();
    }

    /**
     * Pendaftar sudah terkirim (menunggu verifikasi) dengan 3 dokumen.
     */
    protected function buatPendaftarTerverifikasi(): Pendaftar
    {
        $p = Pendaftar::factory()->denganUser()->menungguVerifikasi()->create();
        foreach (['ktp', 'kk', 'ijazah'] as $jenis) {
            \App\Models\Dokumen::factory()->create(['pendaftar_id' => $p->id, 'jenis' => $jenis]);
        }
        return $p;
    }

    /**
     * Pendaftar gugur dengan 5 dokumen lengkap.
     */
    protected function buatPendaftarGugur(): Pendaftar
    {
        return Pendaftar::factory()->denganUser()->gugur()->denganDokumen()->create();
    }

    /**
     * Pendaftar lulus verifikasi dengan 5 dokumen lengkap.
     */
    protected function buatPendaftarLulus(): Pendaftar
    {
        return Pendaftar::factory()->denganUser()->lulusVerifikasi()->denganDokumen()->create();
    }
}
