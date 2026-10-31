<!DOCTYPE html>
<html lang="id" id="page-top">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Admin') — PSB {{ config('psb.nama_sekolah') }}</title>

    <link href="{{ asset('Assets/Dashboard/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="{{ asset('Assets/Dashboard/css/sb-admin-2.min.css') }}" rel="stylesheet" type="text/css">
</head>

<body id="page-top">

    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
                <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-graduation-cap"></i></div>
                <div class="sidebar-brand-text mx-3">PSB <sup>SMKN 2</sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.pendaftar*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/admin/pendaftar') }}">
                    <i class="fas fa-fw fa-users"></i> <span>Pendaftar</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.verifikasi*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/admin/verifikasi') }}">
                    <i class="fas fa-fw fa-check-double"></i> <span>Verifikasi</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.nilai*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/admin/nilai') }}">
                    <i class="fas fa-fw fa-chart-bar"></i> <span>Nilai & Ranking</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.jurusan*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.jurusan.index') }}">
                    <i class="fas fa-fw fa-layer-group"></i> <span>Jurusan & Kuota</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.jadwal*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.jadwal.index') }}">
                    <i class="fas fa-fw fa-calendar-alt"></i> <span>Jadwal PSB</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.pengumuman*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/admin/pengumuman') }}">
                    <i class="fas fa-fw fa-bullhorn"></i> <span>Pengumuman</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.laporan*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/admin/laporan') }}">
                    <i class="fas fa-fw fa-file-export"></i> <span>Laporan</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('admin.audit*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/admin/audit') }}">
                    <i class="fas fa-fw fa-history"></i> <span>Audit Log</span></a>
            </li>

            <div class="sidebar-shrink">
                <hr class="sidebar-divider">
            </div>

            <div class="text-center small text-gray-400 mb-2">{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger btn-sm btn-block">Logout</button>
            </form>
        </ul>
        <!-- End Sidebar -->

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <div class="container-fluid">

                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    @endif

                    @yield('content')

                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>&copy; {{ date('Y') }} {{ config('psb.nama_sekolah') }}</span>
                    </div>
                </div>
            </footer>

        </div>

    </div>

    <script src="{{ asset('Assets/Dashboard/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('Assets/Dashboard/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('Assets/Dashboard/js/sb-admin-2.min.js') }}"></script>
    @stack('scripts')
</body>

</html>
