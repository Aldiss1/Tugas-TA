<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Diterima — {{ $p->nomor_pendaftaran }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; margin: 0; background: #f4f4f4; }
        .page { background: #fff; max-width: 800px; margin: 24px auto; padding: 48px 56px; box-shadow: 0 0 8px rgba(0,0,0,.15); }
        .kop { display: flex; align-items: center; gap: 16px; border-bottom: 3px double #000; padding-bottom: 12px; }
        .kop img { width: 72px; }
        .kop h1 { font-size: 20px; margin: 0; text-transform: uppercase; }
        .kop p { margin: 2px 0; font-size: 13px; }
        .judul { text-align: center; margin: 28px 0 24px; }
        .judul h2 { font-size: 16px; margin: 0; text-decoration: underline; }
        .judul p { margin: 4px 0 0; font-size: 13px; }
        table.data { margin: 18px 0; font-size: 15px; }
        table.data td { padding: 3px 0; vertical-align: top; }
        table.data td.k { width: 190px; }
        table.data td.s { width: 8px; }
        .ttd { margin-top: 48px; display: flex; justify-content: flex-end; }
        .ttd .blok { text-align: center; font-size: 14px; }
        .ttd .spasi { height: 72px; }
        .nomor-surat { font-size: 14px; margin-bottom: 18px; }
        .aksi { max-width: 800px; margin: 0 auto 8px; text-align: right; }
        .aksi button { font: 14px sans-serif; padding: 8px 18px; background: #103cbe; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { box-shadow: none; margin: 0; padding: 24px 40px; max-width: none; }
            .aksi { display: none; }
        }
    </style>
</head>

<body>
    <div class="aksi">
        <button onclick="window.print()">🖨 Cetak / Simpan PDF</button>
    </div>

    <div class="page">
        <div class="kop">
            <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo">
            <div>
                <h1>{{ $kop['nama'] }}</h1>
                <p>{{ $kop['alamat'] }}</p>
                <p>Tahun Pelajaran {{ $kop['tahun_ajaran'] }}</p>
            </div>
        </div>

        <p class="nomor-surat">
            Nomor : 421.3/{{ strlen($p->nomor_pendaftaran) }}/{{ now()->format('Y') }}/PSB-SMKN2SGS
        </p>

        <div class="judul">
            <h2>SURAT KETERANGAN DITERIMA</h2>
            <p>SEBAGAI CALON SISWA BARU</p>
        </div>

        <table class="data">
            <tr><td class="k">Nomor Pendaftaran</td><td class="s">:</td><td><strong>{{ $p->nomor_pendaftaran }}</strong></td></tr>
            <tr><td class="k">Nama Lengkap</td><td class="s">:</td><td><strong>{{ $p->nama_lengkap }}</strong></td></tr>
            <tr><td class="k">NISN</td><td class="s">:</td><td>{{ $p->nisn }}</td></tr>
            <tr><td class="k">Tempat, Tgl. Lahir</td><td class="s">:</td><td>{{ $p->tempat_lahir }}, {{ $p->tanggal_lahir->format('d F Y') }}</td></tr>
            <tr><td class="k">Asal Sekolah</td><td class="s">:</td><td>{{ $p->asal_sekolah }}</td></tr>
            <tr><td class="k">Jurusan Diterima</td><td class="s">:</td><td><strong>{{ $p->hasil->jurusan->nama }} ({{ $p->hasil->jurusan->kode }})</strong></td></tr>
            <tr><td class="k">Peringkat Seleksi</td><td class="s">:</td><td>{{ $p->hasil->perankingan }} (via pilihan {{ $p->hasil->via_pilihan }})</td></tr>
        </table>

        <p>
            Dengan ini kami nyatakan bahwa calon siswa yang bersangkutan <strong>DIterIMA</strong> menjadi
            siswa baru {{ $kop['nama'] }} tahun pelajaran {{ $kop['tahun_ajaran'] }} pada program keahlian
            <strong>{{ $p->hasil->jurusan->nama }}</strong>, berdasarkan hasil seleksi Penerimaan Siswa Baru.
        </p>
        <p>
            Surat keterangan ini diberikan untuk dipergunakan sebagaimana mestinya. Calon siswa yang diterima
            diwajibkan mengikuti orientasi sekolah sesuai jadwal yang ditentukan.
        </p>

        <div class="ttd">
            <div class="blok">
                <div>Singosari, {{ now()->format('d F Y') }}</div>
                <div>Kepala {{ $kop['nama'] }},</div>
                <div class="spasi"></div>
                <div><strong><u>Sumijah, S.Pd., M.Si.</u></strong></div>
                <div>NIP. —</div>
            </div>
        </div>
    </div>
</body>

</html>
