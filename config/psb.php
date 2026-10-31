<?php

return [
    'nama_sekolah' => 'SMK Negeri 2 Singosari',
    'alamat' => 'Jl. Perusahaan No. 20, Singosari, Kab. Malang',
    'tahun_ajaran' => '2026/2027',
    'siswa_per_rombel' => 36,
    'bobot' => [
        'rapor' => 0.4,
        'prestasi' => 0.3,
        'kunjungan' => 0.3,
    ],
    'jenis_dokumen' => ['ktp', 'kk', 'ijazah', 'rapor', 'foto'],
    'link_verifikasi_expiry' => 7 * 24 * 60, // menit
    'lock_login' => [
        'percobaan' => 3,
        'durasi_menit' => 15,
    ],
];
