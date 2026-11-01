<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PSB SMKN 2 Singosari</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="font-sans min-h-screen bg-gradient-to-br from-slate-50 via-brand-50/40 to-sky-50 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 p-8">
            <div class="text-center mb-8">
                <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" alt="Logo" class="h-16 w-16 mx-auto object-contain">
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">Masuk</h1>
                <p class="mt-1 text-sm text-slate-500">PSB {{ config('psb.nama_sekolah') }}</p>
            </div>

            @if (session('status'))
                <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert variant="error" class="mb-4">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="email Anda">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input id="password" type="password" name="password" required
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Password">
                </div>
                <div class="flex items-center gap-2">
                    <input id="remember" type="checkbox" name="remember"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <label for="remember" class="text-sm text-slate-600">Ingat saya</label>
                </div>
                <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition shadow-sm">
                    Masuk
                </button>
            </form>
        </div>
        <p class="text-center text-sm text-slate-500 mt-4">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">Daftar sekarang</a>
        </p>
    </div>

</body>

</html>
