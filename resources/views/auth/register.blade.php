<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Daftar — PSB SMKN 2 Singosari</title>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="row border rounded-5 p-3 bg-white shadow box-area">

            <div class="col-md-6 rounded-5 d-flex justify-content-center align-items-center flex-column left-box"
                style="background: #103cbe;">
                <div class="featured-image mb-5">
                    <img src="{{ asset('Assets/Img-Login/Logo-SMk.png') }}" class="img-fluid" style="width: 200px;">
                </div>
                <p class="text-white fs-2 mb-3"
                    style="font-family: 'Courier New', Courier, monospace; font-weight: 600;">
                    Daftar Akun</p>
                <small class="text-white text-wrap text-center mt-3"
                    style="width: 17rem;font-family: 'Courier New', Courier, monospace;">
                    Sistem Penerimaan Siswa Baru SMKN 2 SINGOSARI.</small>
            </div>

            <div class="col-md-6 right-box">
                <div class="row align-items-center">
                    <div class="header-text mb-2 text-center mt-4">
                        <h2>Registrasi</h2>
                        <p>Buat akun untuk mendaftar PSB</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="mb-2">
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="form-control form-control-lg bg-light fs-6" placeholder="Nama Lengkap" required>
                        </div>
                        <div class="mb-2">
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="form-control form-control-lg bg-light fs-6" placeholder="Email" required>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="nisn" value="{{ old('nisn') }}"
                                class="form-control form-control-lg bg-light fs-6" placeholder="NISN (10 digit)"
                                maxlength="10" required>
                        </div>
                        <div class="mb-2">
                            <input type="password" name="password"
                                class="form-control form-control-lg bg-light fs-6"
                                placeholder="Password (minimal 8 karakter)" required>
                        </div>
                        <div class="mb-4">
                            <input type="password" name="password_confirmation"
                                class="form-control form-control-lg bg-light fs-6" placeholder="Ulangi Password" required>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-lg btn-primary w-100 fs-6">Daftar</button>
                        </div>
                    </form>

                    <div class="text-center mb-3">
                        <small>Sudah punya akun? <a href="{{ route('login') }}">Login</a></small>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>

</html>
