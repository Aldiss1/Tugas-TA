<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Diterima — {{ $p->nomor_pendaftaran }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-area { box-shadow: none !important; margin: 0 !important; max-width: none !important; border: 0 !important; }
            @page { size: A4; margin: 18mm 16mm; }
        }
    </style>
</head>

<body class="font-sans bg-slate-200 min-h-screen py-8 print:py-0">

    <div class="no-print max-w-[210mm] mx-auto px-4 mb-4 flex justify-end">
        <button onclick="window.print()"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow transition">
            <x-icon-printer class="h-4 w-4" />
            Cetak / Simpan PDF
        </button>
    </div>

    <div class="print-area max-w-[210mm] mx-auto bg-white shadow-lg rounded-sm px-10 py-12 text-slate-900" style="font-family: 'Times New Roman', Times, serif;">
        {{-- Kop surat --}}
        <div class="flex items-center gap-4 border-b-4 border-double border-slate-800 pb-4">
            <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" class="h-20 w-20 object-contain">
            <div class="flex-1 text-center">
                <div class="text-lg font-bold uppercase tracking-wide">{{ $kop['nama'] }}</div>
                <div class="text-sm">{{ $kop['alamat'] }}</div>
                <div class="text-sm">Tahun Pelajaran {{ $kop['tahun_ajaran'] }}</div>
            </div>
            <div class="w-20"></div>
        </div>

        <div class="mt-6 text-sm">
            Nomor : 421.3/{{ (int) strlen($p->nomor_pendaftaran) }}/{{ now()->format('Y') }}/PSB-SMKN2SGS
        </div>

        <div class="mt-8 text-center">
            <h1 class="text-xl font-bold uppercase underline underline-offset-4">SURAT KETERANGAN DITERIMA</h1>
            <div class="mt-1 text-base">SEBAGAI CALON SISWA BARU</div>
        </div>

        <table class="w-full mt-6 text-sm">
            <tr><td class="w-48 py-1">Nomor Pendaftaran</td><td class="w-4 align-top">:</td><td class="font-bold">{{ $p->nomor_pendaftaran }}</td></tr>
            <tr><td class="py-1">Nama Lengkap</td><td class="align-top">:</td><td class="font-bold">{{ $p->nama_lengkap }}</td></tr>
            <tr><td class="py-1">NISN</td><td class="align-top">:</td><td>{{ $p->nisn }}</td></tr>
            <tr><td class="py-1">Tempat, Tgl. Lahir</td><td class="align-top">:</td><td>{{ $p->tempat_lahir }}, {{ $p->tanggal_lahir->format('d F Y') }}</td></tr>
            <tr><td class="py-1">Asal Sekolah</td><td class="align-top">:</td><td>{{ $p->asal_sekolah }}</td></tr>
            <tr><td class="py-1">Jurusan Diterima</td><td class="align-top">:</td><td class="font-bold">{{ $p->hasil->jurusan->nama }} ({{ $p->hasil->jurusan->kode }})</td></tr>
            <tr><td class="py-1">Peringkat Seleksi</td><td class="align-top">:</td><td>{{ $p->hasil->perankingan }} (via pilihan {{ $p->hasil->via_pilihan }})</td></tr>
        </table>

        <p class="mt-6 text-sm leading-7 text-justify">
            Dengan ini kami nyatakan bahwa calon siswa yang bersangkutan <strong>DITERIMA</strong> menjadi
            siswa baru {{ $kop['nama'] }} tahun pelajaran {{ $kop['tahun_ajaran'] }} pada program keahlian
            <strong>{{ $p->hasil->jurusan->nama }}</strong>, berdasarkan hasil seleksi Penerimaan Siswa Baru.
        </p>
        <p class="mt-4 text-sm leading-7 text-justify">
            Surat keterangan ini diberikan untuk dipergunakan sebagaimana mestinya. Calon siswa yang diterima
            diwajibkan mengikuti orientasi sekolah sesuai jadwal yang ditentukan.
        </p>

        <div class="mt-12 flex justify-end">
            <div class="text-center text-sm">
                <div>Singosari, {{ now()->format('d F Y') }}</div>
                <div>Kepala {{ $kop['nama'] }},</div>
                <div class="h-24"></div>
                <div class="font-bold underline">Sumijah, S.Pd., M.Si.</div>
                <div>NIP. —</div>
            </div>
        </div>
    </div>

</body>

</html>
