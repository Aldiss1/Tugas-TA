<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Calon Siswa') — {{ config('psb.nama_sekolah') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #103cbe;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" style="width: 38px; margin-right: .5rem;">
                PSB {{ config('psb.nama_sekolah') }}
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 me-3 small d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <a class="nav-link" href="{{ route('home') }}">Beranda</a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline ms-2">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
