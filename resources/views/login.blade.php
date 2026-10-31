<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PSB SMKN 2 Singosari</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Nunito', system-ui, sans-serif; background: #f4f6fb; }</style>
</head>

<body class="d-flex align-items-center" style="min-height: 100vh;">

    <div class="container" style="max-width: 420px;">
        <div class="card border-0 shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" class="mb-2" style="width: 80px;">
                    <h1 class="h4 mb-1">Login</h1>
                    <div class="text-muted small">PSB {{ config('psb.nama_sekolah') }}</div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success py-2">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="email Anda" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Ingat saya</label>
                    </div>
                    <button class="btn btn-primary w-100">Masuk</button>
                </form>

                <div class="text-center small text-muted mt-3 mb-0">
                    Belum punya akun? <a href="{{ route('register') }}" class="fw-semibold">Daftar sekarang</a>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
