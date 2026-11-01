<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Calon Siswa') — {{ config('psb.nama_sekolah') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="font-sans min-h-screen bg-slate-50">

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
                    <a href="{{ route('status') }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('status') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100' }} transition">Status</a>
                    <a href="{{ route('pendaftaran') }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('pendaftaran*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100' }} transition">Pendaftaran</a>
                    <a href="{{ route('akun') }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('akun') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100' }} transition">Akun</a>
                    <div class="hidden sm:flex items-center gap-2 pl-3 ml-1 border-l border-slate-200">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold">
                            {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="text-sm font-medium text-slate-700 max-w-[140px] truncate">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-1">
                        @csrf
                        <button class="px-3 py-2 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-100 transition">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-8">
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

</body>

</html>
