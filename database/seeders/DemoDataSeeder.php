<?php

namespace Database\Seeders;

use App\Enums\JenisDokumen;
use App\Enums\StatusPendaftar;
use App\Models\Dokumen;
use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed data demo untuk presentasi/demo. IDEMPOTEN:
     * lewati jika sudah ada pendaftar demo (penanda: NISN berawalan '999').
     */
    public function run(): void
    {
        if (Pendaftar::where('nisn', 'like', '999%')->exists()) {
            return; // sudah di-seed, lewati (idempoten)
        }

        $this->buatDemoPendaftar();
    }

    private function buatDemoPendaftar(): void
    {
        $j1 = Jurusan::where('kode', 'TKJ')->first();
        $j2 = Jurusan::where('kode', 'RPL')->first();

        // 1. Draf (belum dikirim, tanpa dokumen)
        $this->buat('9990000001', 'Siti Rahmawati', StatusPendaftar::DRAF, null, null, null, null);

        // 2-3. Menunggu verifikasi (dengan 5 dokumen lengkap)
        $this->buat('9990000002', 'Budi Santoso', StatusPendaftar::MENUNGGU_VERIFIKASI, $j1);
        $this->buat('9990000003', 'Citra Lestari', StatusPendaftar::MENUNGGU_VERIFIKASI, $j2);

        // 4. Gugur (dengan alasan)
        $this->buat('9990000004', 'Dedi Kurniawan', StatusPendaftar::GUGUR, $j1, alasan: 'NIK tidak cocok dengan nama');

        // 5-8. Lulus verifikasi → hasil beragam (diterima/cadangan/tidak diterima)
        $this->buat('9990000005', 'Eka Putri', StatusPendaftar::DITERIMA, $j1, nilai: [90, 85, 92], statusAkhir: 'diterima', perankingan: 1);
        $this->buat('9990000006', 'Fajar Nugroho', StatusPendaftar::DITERIMA, $j1, nilai: [88, 80, 85], statusAkhir: 'diterima', perankingan: 2);
        $this->buat('9990000007', 'Gita Sari', StatusPendaftar::CADANGAN, $j1, nilai: [75, 70, 72], statusAkhir: 'cadangan', perankingan: 3);
        $this->buat('9990000008', 'Hendra Wijaya', StatusPendaftar::TIDAK_DITERIMA, $j1, nilai: [60, 55, 60], statusAkhir: 'tidak_diterima', perankingan: 8);
    }

    private function buat(
        string $nisn,
        string $nama,
        StatusPendaftar $status,
        ?Jurusan $jurusan = null,
        ?array $nilai = null,
        ?string $statusAkhir = null,
        ?int $perankingan = null,
        ?string $alasan = null,
    ): void {
        $user = User::factory()->create(['nisn' => $nisn]);
        $p = Pendaftar::factory()
            ->create([
                'pengguna_id' => $user->id,
                'nisn' => $nisn,
                'nama_lengkap' => $nama,
                'status' => $status->value,
                'alasan_gugur' => $alasan,
            ]);

        if ($jurusan) {
            PendaftarPilihan::create([
                'pendaftar_id' => $p->id,
                'jurusan_id' => $jurusan->id,
                'prioritas' => 1,
            ]);
        }

        // Semua kecuali draf punya 5 dokumen lengkap
        if ($status !== StatusPendaftar::DRAF) {
            foreach (JenisDokumen::cases() as $jenis) {
                Dokumen::factory()->create(['pendaftar_id' => $p->id, 'jenis' => $jenis]);
            }
        }

        // Nilai + hasil untuk yang sudah diproses
        if ($nilai && $statusAkhir) {
            [$r, $p2, $k] = $nilai;
            $bobot = config('psb.bobot');
            \App\Models\NilaiSeleksi::factory()->for($p)->create([
                'nilai_rapor' => $r,
                'nilai_prestasi' => $p2,
                'nilai_kunjungan' => $k,
                'total' => $bobot['rapor'] * $r + $bobot['prestasi'] * $p2 + $bobot['kunjungan'] * $k,
            ]);
            Hasil::factory()->for($p)->create([
                'status_akhir' => $statusAkhir,
                'jurusan_id' => $jurusan?->id,
                'perankingan' => $perankingan ?? 1,
                'via_pilihan' => 1,
            ]);
        }
    }
}
