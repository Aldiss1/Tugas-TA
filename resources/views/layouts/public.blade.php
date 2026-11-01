<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PSB') — {{ config('psb.nama_sekolah') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="font-sans min-h-screen flex flex-col bg-slate-50">

    <nav class="sticky top-0 z-40 bg-white/90 backdrop-blur ring-1 ring-slate-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" class="h-9 w-9 object-contain">
                        <span class="font-bold text-slate-800 leading-tight">
                            PSB<br class="sm:hidden">
                            <span class="hidden sm:inline"> {{ config('psb.nama_sekolah') }}</span>
                        </span>
                    </a>
                    <div class="flex items-center gap-1 sm:gap-2">
                        <a href="{{ route('home') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Beranda</a>
                        <a href="{{ route('home') }}#jadwal" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Jadwal</a>
                        <a href="{{ route('home') }}#faq" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition hidden sm:block">FAQ</a>
                        @guest
                            <a href="{{ route('login') }}" class="ml-1 px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Login</a>
                            <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white transition">Daftar</a>
                        @else
                            @if (auth()->user()->role === 'admin')
                                <a href="/admin" class="ml-1 px-4 py-2 rounded-lg text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white transition">Dashboard Admin</a>
                            @else
                                <a href="{{ route('status') }}" class="ml-1 px-4 py-2 rounded-lg text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white transition">Dashboard Saya</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="ml-1">
                                @csrf
                                <button class="px-3 py-2 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-100 transition">Logout</button>
                            </form>
                        @endauth
                    </div>
                </div>
        </div>
    </nav>

    <main class="flex-1 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            @if (session('status'))
                <x-alert variant="success">{{ session('status') }}</x-alert>
            @endif
            @if ($errors->any())
                <x-alert variant="error">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif
            @yield('content')
        </div>
    </main>

    <footer class="bg-slate-900 text-slate-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" class="h-10 w-10 object-contain bg-white rounded-lg p-0.5">
                    <div>
                        <div class="font-semibold text-white">{{ config('psb.nama_sekolah') }}</div>
                        <div class="text-sm text-slate-400">{{ config('psb.alamat') }}</div>
                    </div>
                </div>
                <div class="text-sm text-slate-400">&copy; {{ date('Y') }} — Penerimaan Siswa Baru TA {{ config('psb.tahun_ajaran') }}</div>
            </div>
        </div>
    </footer>

</body>

</html>
