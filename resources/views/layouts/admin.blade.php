<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — PSB {{ config('psb.nama_sekolah') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="font-sans h-full">

    {{-- Backdrop mobile --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-900/50 hidden lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="admin-sidebar"
        class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0 transition-transform duration-200 bg-slate-900 flex flex-col">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-800">
            <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" class="h-9 w-9 object-contain bg-white rounded-lg p-0.5">
            <div class="leading-tight">
                <div class="text-white font-bold text-sm">PSB SMKN 2</div>
                <div class="text-slate-400 text-[11px]">Singosari — Admin</div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
            <div>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                    <x-icon-home class="h-5 w-5" /> Dashboard
                </a>
            </div>

            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Data</div>
                <div class="space-y-1">
                    <a href="{{ url('/admin/pendaftar') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.pendaftar*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-users class="h-5 w-5" /> Pendaftar
                    </a>
                    <a href="{{ route('admin.jurusan.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.jurusan*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-academic-cap class="h-5 w-5" /> Jurusan & Kuota
                    </a>
                </div>
            </div>

            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Proses</div>
                <div class="space-y-1">
                    <a href="{{ url('/admin/verifikasi') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.verifikasi*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-clipboard-check class="h-5 w-5" /> Verifikasi
                    </a>
                    <a href="{{ url('/admin/nilai') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.nilai*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-chart-bar class="h-5 w-5" /> Nilai & Ranking
                    </a>
                    <a href="{{ url('/admin/pengumuman') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.pengumuman*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-bell class="h-5 w-5" /> Pengumuman
                    </a>
                </div>
            </div>

            <div>
                <div class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Sistem</div>
                <div class="space-y-1">
                    <a href="{{ route('admin.jadwal.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.jadwal*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-calendar class="h-5 w-5" /> Jadwal PSB
                    </a>
                    <a href="{{ url('/admin/laporan') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.laporan*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-document-text class="h-5 w-5" /> Laporan
                    </a>
                    <a href="{{ url('/admin/audit') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.audit*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <x-icon-clipboard class="h-5 w-5" /> Audit Log
                    </a>
                </div>
            </div>
        </nav>

        <div class="px-4 py-4 border-t border-slate-800">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-brand-600 text-white text-sm font-bold">
                    {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400">Administrator</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-2 text-sm font-medium text-slate-200 transition">
                    <x-icon-logout class="h-4 w-4" /> Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64 flex flex-col min-h-full">
        <header class="sticky top-0 z-20 h-16 bg-white/90 backdrop-blur ring-1 ring-slate-100 flex items-center gap-3 px-4 sm:px-6">
            <button id="btn-menu" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                <x-icon-menu class="h-5 w-5" />
            </button>
            <h1 class="text-lg font-bold tracking-tight text-slate-900 truncate">@yield('title', 'Dashboard')</h1>
            <div class="ml-auto text-sm text-slate-500 hidden sm:block">{{ config('psb.nama_sekolah') }}</div>
        </header>

        <main class="flex-1 p-4 sm:p-6 space-y-6">
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
        </main>
    </div>

    <script>
        (function () {
            var sb = document.getElementById('admin-sidebar');
            var bd = document.getElementById('sidebar-backdrop');
            var btn = document.getElementById('btn-menu');
            function buka() { sb.classList.remove('-translate-x-full'); bd.classList.remove('hidden'); }
            function tutup() { sb.classList.add('-translate-x-full'); bd.classList.add('hidden'); }
            btn.addEventListener('click', buka);
            bd.addEventListener('click', tutup);
        })();
    </script>

</body>

</html>
