<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\JadwalPsb;
use App\Models\Jurusan;
use App\Models\Kuota;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin PSB',
            'email' => 'admin@smkn2.sch.id',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'admin1234')),
            'role' => Role::ADMIN->value,
        ]);

        $jurusan = [
            'TKJ' => ['Teknik Komputer dan Jaringan', 3, 'Mempersiapkan teknisi jaringan komputer, server, dan keamanan jaringan.'],
            'RPL' => ['Rekayasa Perangkat Lunak', 3, 'Membangun aplikasi web, mobile, dan perangkat lunak sesuai standar industri.'],
            'ANM' => ['Animasi', 2, 'Membuat karya animasi 2D/3D untuk produksi media dan industri kreatif.'],
            'BCS' => ['Produksi dan Siaran Program Televisi', 2, 'Menguasai produksi, penyiaran, dan penyuntingan program televisi.'],
            'TAV' => ['Teknik Audio Video', 2, 'Menguasai sistem audio, video, dan multimedia untuk produksi pertunjukan.'],
            'TEI' => ['Teknik Elektronika Industri', 2, 'Merancang, mengoperasikan, dan memelihara sistem kontrol elektronika industri.'],
            'MKA' => ['Mekatronika', 2, 'Menggabungkan mekanik, elektronika, dan pemrograman dalam otomasi sistem.'],
        ];

        $perRombel = (int) config('psb.siswa_per_rombel');

        foreach ($jurusan as $kode => [$nama, $rombel, $deskripsi]) {
            $kuota = $rombel * $perRombel;
            $j = Jurusan::create([
                'kode' => $kode,
                'nama' => $nama,
                'deskripsi' => $deskripsi,
                'rombel' => $rombel,
                'aktif' => true,
            ]);
            Kuota::create([
                'jurusan_id' => $j->id,
                'kuota' => $kuota,
                'cadangan' => (int) ceil($kuota * 0.1),
                'terisi' => 0,
            ]);
        }

        $jadwal = [
            'pendaftaran' => ['2026-06-01 07:00:00', '2026-07-31 23:59:59'],
            'verifikasi' => ['2026-08-01 07:00:00', '2026-08-15 23:59:59'],
            'perbaikan' => ['2026-08-01 07:00:00', '2026-08-20 23:59:59'],
            'seleksi' => ['2026-08-16 07:00:00', '2026-08-30 23:59:59'],
            'pengumuman' => ['2026-09-01 07:00:00', '2026-09-10 23:59:59'],
        ];

        foreach ($jadwal as $tahap => [$mulai, $selesai]) {
            JadwalPsb::create([
                'tahap' => $tahap,
                'mulai' => $mulai,
                'selesai' => $selesai,
            ]);
        }
    }
}
