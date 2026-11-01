@extends('layouts.portal')

@section('title', 'Pendaftaran')

@section('content')
    <div class="grid lg:grid-cols-12 gap-6 items-start">
        {{-- ============ FORM ============ --}}
        <div class="lg:col-span-8 space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Formulir Pendaftaran</h1>
                @if ($p?->nomor_pendaftaran)
                    <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700 ring-1 ring-brand-200 font-mono">
                        No. Pendaftaran: {{ $p->nomor_pendaftaran }}
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('pendaftaran') }}" id="form-pendaftaran" novalidate>
                @csrf

                {{-- 1. Data Diri --}}
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6" id="seksi-data">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-brand-600 text-white text-sm font-bold">1</span>
                        <h2 class="font-bold text-slate-900">Data Diri</h2>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <x-input label="Nama Lengkap" name="nama_lengkap" required value="{{ old('nama_lengkap', $p?->nama_lengkap) }}" />
                        </div>
                        <div>
                            <x-input label="Tempat Lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $p?->tempat_lahir) }}" />
                        </div>
                        <div>
                            <x-input label="Tanggal Lahir" name="tanggal_lahir" type="date" required value="{{ old('tanggal_lahir', $p?->tanggal_lahir?->format('Y-m-d')) }}" />
                        </div>
                        <div>
                            <x-select label="Jenis Kelamin" name="jenis_kelamin" required>
                                <option value="">—</option>
                                <option value="L" @selected(old('jenis_kelamin', $p?->jenis_kelamin) === 'L')>Laki-laki</option>
                                <option value="P" @selected(old('jenis_kelamin', $p?->jenis_kelamin) === 'P')>Perempuan</option>
                            </x-select>
                        </div>
                        <div>
                            <x-input label="Agama" name="agama" value="{{ old('agama', $p?->agama) }}" />
                        </div>
                        <div>
                            <x-input label="Asal Sekolah (SMP/MTs)" name="asal_sekolah" value="{{ old('asal_sekolah', $p?->asal_sekolah) }}" />
                        </div>
                        <div>
                            <x-input label="NIK" name="nik" value="{{ old('nik', $p?->nik) }}" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                            <textarea name="alamat" rows="2"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('alamat', $p?->alamat) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">NISN</label>
                            <input type="text" class="block w-full rounded-lg bg-slate-50 border-slate-200 text-sm text-slate-500" value="{{ auth()->user()->nisn }}" disabled>
                            <p class="mt-1 text-xs text-slate-400">NISN dari akun — tidak dapat diubah.</p>
                        </div>
                    </div>
                </div>

                {{-- 2. Orang Tua / Wali --}}
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6" id="seksi-ortu">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-brand-600 text-white text-sm font-bold">2</span>
                        <h2 class="font-bold text-slate-900">Orang Tua / Wali</h2>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <x-input label="Nama" name="nama_orang_tua" value="{{ old('nama_orang_tua', $p?->nama_orang_tua) }}" />
                        <x-input label="Pekerjaan" name="pekerjaan_orang_tua" value="{{ old('pekerjaan_orang_tua', $p?->pekerjaan_orang_tua) }}" />
                        <x-input label="No. Telepon" name="telepon_orang_tua" value="{{ old('telepon_orang_tua', $p?->telepon_orang_tua) }}" />
                    </div>
                </div>

                {{-- 3. Pilihan Jurusan --}}
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6" id="seksi-jurusan">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-brand-600 text-white text-sm font-bold">3</span>
                        <h2 class="font-bold text-slate-900">Pilihan Jurusan</h2>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <x-select label="Pilihan 1 (wajib)" name="pilihan_1" required>
                            <option value="">— pilih jurusan —</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" @selected((int) old('pilihan_1', $p?->pilihan()->where('prioritas', 1)->first()?->jurusan_id) === $j->id)>
                                    {{ $j->nama }} — sisa {{ $j->kuota->sisa() ?? 0 }}
                                </option>
                            @endforeach
                        </x-select>
                        <div>
                            <x-select label="Pilihan 2 (opsional)" name="pilihan_2">
                                <option value="">— tidak ada —</option>
                                @foreach ($jurusan as $j)
                                    <option value="{{ $j->id }}" @selected((int) old('pilihan_2', $p?->pilihan()->where('prioritas', 2)->first()?->jurusan_id) === $j->id)>
                                        {{ $j->nama }} — sisa {{ $j->kuota->sisa() ?? 0 }}
                                    </option>
                                @endforeach
                            </x-select>
                            <p class="mt-1 text-xs text-slate-400">Pilihan 2 harus berbeda dari Pilihan 1.</p>
                        </div>
                    </div>
                </div>

                {{-- 4. Dokumen --}}
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6" id="seksi-dokumen">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-brand-600 text-white text-sm font-bold">4</span>
                        <h2 class="font-bold text-slate-900">Dokumen Persyaratan</h2>
                    </div>
                    <p class="text-sm text-slate-500 mb-5">PDF / JPG / PNG, maksimal 2 MB per file. Tersimpan otomatis saat file dipilih.</p>
                    @php $mapDok = $p?->dokumen()->get()->keyBy(fn ($d) => $d->jenis->value); @endphp
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach ($jenisDokumen as $jenis)
                            @php
                                $dok = $mapDok[$jenis->value] ?? null;
                                $tersimpan = $dok && $dok->diunggah_at;
                            @endphp
                            <div class="rounded-xl ring-1 ring-dashed ring-slate-300 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-slate-800">{{ $jenis->label() }}</span>
                                    @if ($tersimpan)
                                        <span id="slot-{{ $jenis->value }}"
                                            class="inline-flex items-center whitespace-nowrap rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">✓ Tersimpan</span>
                                    @else
                                        <span id="slot-{{ $jenis->value }}"
                                            class="inline-flex items-center whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">Belum</span>
                                    @endif
                                </div>
                                <input type="file" data-jenis="{{ $jenis->value }}"
                                    form="form-dok-{{ $jenis->value }}" name="file"
                                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 file:cursor-pointer"
                                    accept=".pdf,.jpg,.jpeg,.png">
                                <div id="err-{{ $jenis->value }}" class="text-rose-600 text-xs mt-2"></div>
                            </div>
                        @endforeach
                    </div>
                    @error('file')
                        <div class="text-rose-600 text-sm mt-3">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-white ring-1 ring-inset ring-slate-300 hover:bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-700 transition">
                        Simpan Draf
                    </button>
                    <button type="button" id="btn-kirim"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white transition shadow-sm">
                        Kirim Pendaftaran
                    </button>
                </div>
            </form>

            {{-- Form upload per slot (DI LUAR form-pendaftaran) --}}
            @foreach ($jenisDokumen as $jenis)
                <form id="form-dok-{{ $jenis->value }}" method="POST" action="{{ url('/pendaftaran/dokumen') }}" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis->value }}">
                </form>
            @endforeach

            <form id="form-kirim" method="POST" action="{{ url('/pendaftaran/kirim') }}" class="hidden">
                @csrf
                <input type="hidden" name="pendaftar_id" value="{{ $p?->id }}">
            </form>
        </div>

        {{-- ============ SIDEBAR PROGRES ============ --}}
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5">
                <h3 class="font-bold text-slate-900 mb-4">Kemajuan</h3>
                <ul class="space-y-3">
                    <li id="ck-data" class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 flex items-center gap-2">
                            <x-icon-document-text class="h-4 w-4 text-slate-400" /> Data Diri
                        </span>
                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">belum</span>
                    </li>
                    <li id="ck-ortu" class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 flex items-center gap-2">
                            <x-icon-user-plus class="h-4 w-4 text-slate-400" /> Orang Tua / Wali
                        </span>
                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">belum</span>
                    </li>
                    <li id="ck-jurusan" class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 flex items-center gap-2">
                            <x-icon-academic-cap class="h-4 w-4 text-slate-400" /> Pilihan Jurusan
                        </span>
                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">belum</span>
                    </li>
                    <li id="ck-dok" class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 flex items-center gap-2">
                            <x-icon-clipboard class="h-4 w-4 text-slate-400" /> Dokumen <span id="dok-count">0</span>/5
                        </span>
                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">belum</span>
                    </li>
                </ul>
            </div>
            <div class="bg-brand-50 rounded-2xl ring-1 ring-brand-100 p-5">
                <p class="text-sm text-brand-900 leading-6">
                    Setelah dikirim, data hanya dapat diubah saat tahap perbaikan (jika digugur verifikasi).
                </p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var doneDok = 0;
            var totalDok = {{ count($jenisDokumen) }};
            var badgeBase = 'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1';
            var badgeCls = {
                ok: badgeBase + ' bg-emerald-50 text-emerald-700 ring-emerald-200',
                belum: badgeBase + ' bg-slate-100 text-slate-500 ring-slate-200',
                proses: badgeBase + ' bg-amber-50 text-amber-700 ring-amber-200',
                gagal: badgeBase + ' bg-rose-50 text-rose-700 ring-rose-200'
            };

            function setBadge(el, text, state) {
                el.textContent = text;
                el.className = badgeCls[state];
            }

            function cekData() {
                var nama = document.querySelector('[name=nama_lengkap]').value.trim();
                var tgl = document.querySelector('[name=tanggal_lahir]').value;
                var jk = document.querySelector('[name=jenis_kelamin]').value;
                return nama && tgl && jk;
            }
            function cekOrtu() {
                return document.querySelector('[name=nama_orang_tua]').value.trim();
            }
            function cekJurusan() {
                return document.querySelector('[name=pilihan_1]').value !== '';
            }

            function updateChecklist() {
                var m = { 'ck-data': cekData(), 'ck-ortu': cekOrtu(), 'ck-jurusan': cekJurusan() };
                Object.keys(m).forEach(function (id) {
                    var b = document.querySelector('#' + id + ' .rounded-full');
                    setBadge(b, m[id] ? '✓ selesai' : 'belum', m[id] ? 'ok' : 'belum');
                });
                var bd = document.querySelector('#ck-dok .rounded-full');
                var teksDok = doneDok === totalDok ? '✓ selesai' : 'belum';
                setBadge(bd, teksDok, doneDok === totalDok ? 'ok' : 'belum');
                document.getElementById('dok-count').textContent = doneDok;
            }

            document.querySelectorAll('input[type=file][data-jenis]').forEach(function (input) {
                input.addEventListener('change', function () {
                    var jenis = this.dataset.jenis;
                    var errEl = document.getElementById('err-' + jenis);
                    var badge = document.getElementById('slot-' + jenis);
                    var form = document.getElementById('form-dok-' + jenis);
                    errEl.textContent = '';
                    if (!this.files.length) return;

                    setBadge(badge, 'mengunggah…', 'proses');
                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    }).then(function (r) {
                        return r.json().then(function (j) { return { ok: r.ok, j: j }; });
                    }).then(function (res) {
                        if (res.ok && res.j.ok) {
                            setBadge(badge, '✓ Tersimpan', 'ok');
                            if (badge.dataset.dicek !== '1') { badge.dataset.dicek = '1'; doneDok++; }
                            updateChecklist();
                        } else {
                            setBadge(badge, 'Gagal', 'gagal');
                            errEl.textContent = res.j.error || 'Gagal mengunggah.';
                        }
                    }).catch(function () {
                        location.reload();
                    });
                });
            });

            // hitung ulang status slot yang sudah tersimpan (reload)
            document.querySelectorAll('[id^=slot-]').forEach(function (b) {
                if (b.classList.contains('bg-emerald-50')) { b.dataset.dicek = '1'; doneDok++; }
            });
            document.querySelectorAll('#seksi-data input, #seksi-data select, #seksi-data textarea').forEach(function (el) {
                if (el.disabled) return;
                el.addEventListener('input', updateChecklist);
                el.addEventListener('change', updateChecklist);
            });
            document.querySelectorAll('#seksi-ortu input').forEach(function (el) {
                el.addEventListener('input', updateChecklist);
            });
            document.querySelector('[name=pilihan_1]').addEventListener('change', updateChecklist);
            updateChecklist();

            // tombol kirim
            document.getElementById('btn-kirim').addEventListener('click', function () {
                var id = @json($p?->id);
                if (!id) {
                    alert('Simpan draf terlebih dahulu (tombol Simpan Draf), lalu unggah semua dokumen.');
                    return;
                }
                if (doneDok < totalDok) {
                    if (!confirm('Dokumen baru ' + doneDok + '/' + totalDok + '. Tetap kirim? Pendaftaran mungkin ditolak karena dokumen belum lengkap.')) return;
                }
                if (!confirm('Kirim pendaftaran? Setelah dikirim, data hanya dapat diubah saat tahap perbaikan.')) return;
                document.getElementById('form-kirim').submit();
            });
        })();
    </script>
@endsection
