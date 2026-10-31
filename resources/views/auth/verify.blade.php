<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email — PSB SMKN 2 Singosari</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>

<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container" style="max-width: 480px;">
        <div class="card border-0 shadow">
            <div class="card-body p-4 text-center">
                <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" class="mb-3" style="width: 80px;" alt="Logo">
                <h4 class="mb-2">Verifikasi Email</h4>
                <p class="text-muted">
                    Email Anda ({{ auth()->user()->email }}) belum terverifikasi.
                    Periksa kotak masuk dan klik tautan verifikasi yang kami kirimkan.
                    Tautan berlaku 7 hari sejak akun dibuat.
                </p>

                @if (session('status'))
                    <div class="alert alert-success py-2">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ url('/verify-email/resend') }}">
                    @csrf
                    <button class="btn btn-primary w-100">Kirim Ulang Tautan Verifikasi</button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="btn btn-link text-muted">Logout</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
