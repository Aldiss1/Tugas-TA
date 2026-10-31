<?php

namespace Tests;

use App\Enums\TahapJadwal;
use App\Models\Dokumen;
use App\Models\JadwalPsb;
use App\Models\Jurusan;
use App\Models\Kuota;
use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
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
            'pilihan_1' => Jurusan::where('kode', 'TKJ')->value('id'),
            'pilihan_2' => Jurusan::where('kode', 'RPL')->value('id'),
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
            Dokumen::factory()->create(['pendaftar_id' => $p->id, 'jenis' => $jenis]);
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

    /**
     * n pendaftar dengan status campuran (untuk uji filter).
     */
    protected function buatDataPendaftar(int $n = 20): void
    {
        $status = ['menunggu_verifikasi', 'lulus_verifikasi', 'gugur', 'diterima'];
        foreach (range(1, $n) as $i) {
            Pendaftar::factory()
                ->set('status', $status[$i % count($status)])
                ->denganPilihan($i % 2 === 0 ? 'TKJ' : 'RPL')
                ->create(['nama_lengkap' => 'Pendaftar '.$i]);
        }
    }

    /**
     * Skenario ranking: specs = [nama => ['nilai' => total, 'p1' => 'J1', 'p2' => 'J2'?, 'rapor'? => n]]
     * prestasi/kunjungan diisi agar total = nilai yang diminta (bobot 40/30/30).
     */
    protected function buatSkenario(array $specs, array $kuota = [], array $cadangan = []): void
    {
        $kodes = array_filter(array_unique(array_merge(
            array_map(fn ($s) => $s['p1'], $specs),
            array_map(fn ($s) => $s['p2'] ?? null, $specs),
            array_keys($kuota),
            array_keys($cadangan)
        )));

        foreach ($kodes as $kode) {
            $k = (int) ($kuota[$kode] ?? 36);
            $j = Jurusan::firstOrCreate(
                ['kode' => $kode],
                ['nama' => 'Jurusan '.$kode, 'rombel' => 1, 'aktif' => true]
            );
            Kuota::updateOrCreate(
                ['jurusan_id' => $j->id],
                [
                    'kuota' => $k,
                    'cadangan' => (int) ($cadangan[$kode] ?? ceil($k * 0.1)),
                    'terisi' => 0,
                ]
            );
        }

        foreach ($specs as $nama => $s) {
            $rapor = (float) ($s['rapor'] ?? $s['nilai']);
            $x = ((float) $s['nilai'] == $rapor)
                ? $rapor
                : ((float) $s['nilai'] - 0.4 * $rapor) / 0.6;

            $p = Pendaftar::factory()->lulusVerifikasi()->create(['nama_lengkap' => (string) $nama]);
            PendaftarPilihan::create([
                'pendaftar_id' => $p->id,
                'jurusan_id' => Jurusan::where('kode', $s['p1'])->value('id'),
                'prioritas' => 1,
            ]);
            if (! empty($s['p2'])) {
                PendaftarPilihan::create([
                    'pendaftar_id' => $p->id,
                    'jurusan_id' => Jurusan::where('kode', $s['p2'])->value('id'),
                    'prioritas' => 2,
                ]);
            }
            NilaiSeleksi::factory()->for($p)->create([
                'nilai_rapor' => $rapor,
                'nilai_prestasi' => round($x, 2),
                'nilai_kunjungan' => round($x, 2),
                'total' => $s['nilai'],
            ]);
        }
    }

    /**
     * Data form input manual admin (lengkap, tanpa file).
     */
    protected function dataManualLengkap(?string $nisn = null): array
    {
        return [
            'nisn' => $nisn ?? fake()->unique()->numerify('##########'),
            'nama_lengkap' => 'Pendaftar Manual',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2008-06-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'asal_sekolah' => 'SMPN 2 Singosari',
            'alamat' => 'Jl. Manual No. 2, Malang',
            'nik' => '3201010101000002',
            'nama_orang_tua' => 'Bapak Manual',
            'pekerjaan_orang_tua' => 'PNS',
            'telepon_orang_tua' => '081299998888',
            'pilihan_1' => Jurusan::where('kode', 'TKJ')->value('id'),
            'pilihan_2' => Jurusan::where('kode', 'RPL')->value('id'),
        ];
    }
}
