<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Login — PSB SMKN 2 Singosari</title>
</head>

<body>

    <!----------------------- Main Container -------------------------->

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <!----------------------- Login Container -------------------------->

        <div class="row border rounded-5 p-3 bg-white shadow box-area">

            <!--------------------------- Left Box ----------------------------->

            <div class="col-md-6 rounded-5 d-flex justify-content-center align-items-center flex-column left-box"
                style="background: #103cbe;">
                <div class="featured-image mb-5">
                    <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" class="img-fluid" style="width: 200px;">
                </div>
                <p class="text-white fs-2 mb-3"
                    style="font-family: 'Courier New', Courier, monospace; font-weight: 600;">
                    Welcome</p>
                <small class="text-white text-wrap text-center mt-3"
                    style="width: 17rem;font-family: 'Courier New', Courier, monospace;">
                    Di Sistem Penerimaan Siswa Baru SMKN 2 SINGOSARI.</small>
            </div>

            <!-------------------- ------ Right Box ---------------------------->

            <div class="col-md-6 right-box">
                <div class="row align-items-center">
                    <div class="header-text mb-2 text-center mt-5">
                        <h2>Silahkan Login</h2>
                        <p>Silahkan login terlebih dahulu untuk membuka aplikasi</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success py-2">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="input-group mb-3 mt-3">
                            <input type="text" name="email" value="{{ old('email') }}"
                                class="form-control form-control-lg bg-light fs-6" placeholder="Email / Username" required>
                        </div>
                        <div class="input-group mb-4">
                            <input type="password" name="password"
                                class="form-control form-control-lg bg-light fs-6" placeholder="Password" required>
                        </div>
                        <div class="input-group mb-3">
                            <button class="btn btn-lg btn-primary w-100 fs-6">Login</button>
                        </div>
                    </form>

                    <div class="text-center mb-3">
                        <small>Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></small>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>

</html>
