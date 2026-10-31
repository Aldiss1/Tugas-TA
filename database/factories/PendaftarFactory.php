<?php

namespace Database\Factories;

use App\Enums\JenisDokumen;
use App\Enums\StatusPendaftar;
use App\Models\Dokumen;
use App\Models\Jurusan;
use App\Models\NilaiSeleksi;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pendaftar>
 */
class PendaftarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_pendaftaran' => 'PSB-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'pengguna_id' => null,
            'sumber' => 'online',
            'nisn' => fake()->unique()->numerify('##########'),
            'nama_lengkap' => fake()->name(),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-16 years', '-14 years')->format('Y-m-d'),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'agama' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha']),
            'asal_sekolah' => 'SMP Negeri 1 Singosari',
            'alamat' => fake()->address(),
            'nik' => fake()->unique()->numerify('3201############'),
            'nama_orang_tua' => fake()->name(),
            'pekerjaan_orang_tua' => fake()->jobTitle(),
            'telepon_orang_tua' => fake()->phoneNumber(),
            'status' => StatusPendaftar::DRAF->value,
            'alasan_gugur' => null,
            'dikirim_at' => null,
        ];
    }

    public function denganUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'pengguna_id' => User::factory(),
        ]);
    }

    public function menungguVerifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPendaftar::MENUNGGU_VERIFIKASI->value,
            'dikirim_at' => now(),
        ]);
    }

    public function lulusVerifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPendaftar::LULUS_VERIFIKASI->value,
            'dikirim_at' => now()->subDay(),
        ]);
    }

    public function gugur(?string $alasan = 'Dokumen tidak sesuai'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPendaftar::GUGUR->value,
            'alasan_gugur' => $alasan,
            'dikirim_at' => now()->subDay(),
        ]);
    }

    /**
     * Buat pilihan jurusan sesuai urutan kode: pertama = prioritas 1, kedua = prioritas 2.
     */
    public function denganPilihan(string ...$kode): static
    {
        return $this->afterCreating(function (Pendaftar $pendaftar) use ($kode) {
            foreach (array_values($kode) as $i => $k) {
                $jurusan = Jurusan::where('kode', $k)->firstOrFail();
                PendaftarPilihan::create([
                    'pendaftar_id' => $pendaftar->id,
                    'jurusan_id' => $jurusan->id,
                    'prioritas' => $i + 1,
                ]);
            }
        });
    }

    public function denganDokumen(): static
    {
        return $this->afterCreating(function (Pendaftar $pendaftar) {
            foreach (JenisDokumen::cases() as $jenis) {
                Dokumen::factory()->create([
                    'pendaftar_id' => $pendaftar->id,
                    'jenis' => $jenis,
                ]);
            }
        });
    }

    public function denganNilai(?float $rapor = null, ?float $prestasi = null, ?float $kunjungan = null): static
    {
        return $this->afterCreating(function (Pendaftar $pendaftar) use ($rapor, $prestasi, $kunjungan) {
            $r = $rapor ?? 70;
            $p = $prestasi ?? 60;
            $k = $kunjungan ?? 80;
            $bobot = config('psb.bobot');
            NilaiSeleksi::factory()->for($pendaftar)->create([
                'nilai_rapor' => $r,
                'nilai_prestasi' => $p,
                'nilai_kunjungan' => $k,
                'total' => $bobot['rapor'] * $r + $bobot['prestasi'] * $p + $bobot['kunjungan'] * $k,
            ]);
        });
    }
}
