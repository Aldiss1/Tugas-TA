<?php

namespace App\Http\Controllers\Portal;

use App\Enums\JenisDokumen;
use App\Exceptions\DokumenBelumLengkap;
use App\Exceptions\DokumenInvalid;
use App\Exceptions\NISNDuplikat;
use App\Exceptions\PendaftaranDitutup;
use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Services\Psb\DokumenService;
use App\Services\Psb\PendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    public function index(Request $request, PendaftaranService $service): View|RedirectResponse
    {
        $user = $request->user();

        $p = Pendaftar::where('pengguna_id', $user->id)->first();

        if ($p && ! $service->bolehRevisi($p)) {
            // Sudah terkirim/lulus verifikasi/dst — data terkunci
            return redirect('/status');
        }

        return view('portal.pendaftaran.index', [
            'p' => $p,
            'jurusan' => Jurusan::with('kuota')->where('aktif', true)->orderBy('nama')->get(),
            'jenisDokumen' => JenisDokumen::cases(),
        ]);
    }

    public function simpan(Request $request, PendaftaranService $service)
    {
        $user = $request->user();

        $p = Pendaftar::where('pengguna_id', $user->id)->first();
        if ($p && ! $service->bolehRevisi($p)) {
            return redirect('/status');
        }

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'agama' => ['nullable', 'string', 'max:20'],
            'asal_sekolah' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nik' => ['nullable', 'string', 'max:20'],
            'nama_orang_tua' => ['nullable', 'string', 'max:100'],
            'pekerjaan_orang_tua' => ['nullable', 'string', 'max:100'],
            'telepon_orang_tua' => ['nullable', 'string', 'max:20'],
            'pilihan_1' => ['required', 'integer', 'exists:jurusan,id'],
            'pilihan_2' => ['nullable', 'integer', 'exists:jurusan,id'],
        ]);

        if ($validated['pilihan_2'] && $validated['pilihan_2'] === $validated['pilihan_1']) {
            return back()->withErrors(['pilihan_2' => 'Pilihan 2 harus berbeda dari pilihan 1.']);
        }

        try {
            $service->simpanDraf($user, $validated);
        } catch (NISNDuplikat) {
            return back()->withErrors(['nisn' => 'NISN sudah terdaftar pada pendaftar lain.']);
        }

        return back()->with('status', 'Draf tersimpan. Lanjutkan unggah dokumen lalu kirim pendaftaran.');
    }

    public function dokumen(Request $request, DokumenService $service, PendaftaranService $pendaftaran)
    {
        $p = Pendaftar::where('pengguna_id', $request->user()->id)->firstOrFail();

        if (! $pendaftaran->bolehRevisi($p)) {
            return $this->balasDokumen(['ok' => false, 'error' => 'Pendaftaran terkunci — dokumen tidak dapat diubah.'], $request, 422);
        }

        $errors = Validator::make(
            $request->all() + ['file' => $request->file('file')],
            [
                'jenis' => ['required', Rule::in(array_column(JenisDokumen::cases(), 'value'))],
                'file' => ['required', 'file'],
            ]
        )->errors();

        if ($errors->isNotEmpty()) {
            return $this->balasDokumen(['ok' => false, 'error' => $errors->first()], $request, 422);
        }

        try {
            $service->simpan($p, $request->input('jenis'), $request->file('file'));
        } catch (DokumenInvalid $e) {
            return $this->balasDokumen(['ok' => false, 'error' => $e->getMessage()], $request, 422);
        }

        return $this->balasDokumen(['ok' => true, 'jenis' => $request->input('jenis')], $request);
    }

    /**
     * XHR → JSON {ok, ...}; non-XHR → redirect + session (perilaku lama).
     */
    private function balasDokumen(array $payload, Request $request, int $status = 200)
    {
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json($payload, $status);
        }

        if (! $payload['ok']) {
            return back()->withErrors(['file' => $payload['error']]);
        }

        return back()->with('status', 'Dokumen tersimpan.');
    }

    public function kirim(Request $request, PendaftaranService $service)
    {
        $request->validate(['pendaftar_id' => ['required', 'integer']]);

        $p = Pendaftar::where('pengguna_id', $request->user()->id)
            ->findOrFail($request->input('pendaftar_id'));

        try {
            $service->kirim($p);
        } catch (PendaftaranDitutup) {
            return back()->withErrors(['pendaftar_id' => 'Periode pendaftaran sudah ditutup.']);
        } catch (DokumenBelumLengkap $e) {
            return back()->withErrors(['pendaftar_id' => $e->getMessage()]);
        } catch (NISNDuplikat) {
            return back()->withErrors(['nisn' => 'NISN sudah terdaftar pada pendaftar lain.']);
        }

        return redirect('/status')->with('status', 'Pendaftaran terkirim. Nomor pendaftaran: '.$p->nomor_pendaftaran);
    }
}
