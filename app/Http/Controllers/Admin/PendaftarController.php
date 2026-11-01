<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisDokumen;
use App\Enums\StatusPendaftar;
use App\Enums\TahapJadwal;
use App\Exceptions\DokumenInvalid;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Jurusan;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use App\Services\Psb\DokumenService;
use App\Services\Psb\JadwalService;
use App\Services\Psb\NomorPendaftaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendaftarController extends Controller
{
    public function index(Request $request): View
    {
        $q = Pendaftar::query()->with(['pilihan.jurusan', 'dokumen']);

        if ($request->filled('status') && StatusPendaftar::tryFrom($request->input('status'))) {
            $q->where('status', $request->input('status'));
        }

        if ($request->filled('jurusan')) {
            $q->whereHas('pilihan', fn ($pq) => $pq->where('jurusan_id', $request->input('jurusan')));
        }

        if ($request->filled('q')) {
            $raw = (string) $request->input('q');
            $t = '%'.mb_strtolower($raw).'%';
            $q->where(function ($qq) use ($t, $raw) {
                $qq->whereRaw('LOWER(nama_lengkap) LIKE ?', [$t])
                    ->orWhere('nisn', 'LIKE', $raw)
                    ->orWhere('nomor_pendaftaran', 'LIKE', '%'.$raw.'%');
            });
        }

        return view('admin.pendaftar.index', [
            'pendaftar' => $q->orderByDesc('id')->paginate(20)->withQueryString(),
            'jurusan' => Jurusan::orderBy('nama')->get(),
            'status' => StatusPendaftar::cases(),
        ]);
    }

    public function show(Pendaftar $pendaftar): View
    {
        $pendaftar->load(['pilihan.jurusan', 'dokumen', 'nilai', 'hasil.jurusan']);

        return view('admin.pendaftar.detail', [
            'p' => $pendaftar,
            'jenisDokumen' => JenisDokumen::cases(),
            'riwayat' => AuditLog::where('target', 'pendaftar:'.$pendaftar->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pendaftar.form', [
            'jurusan' => Jurusan::orderBy('nama')->get(),
            'jenisDokumen' => JenisDokumen::cases(),
        ]);
    }

    /**
     * Input manual (offline) oleh admin.
     */
    public function store(Request $request, JadwalService $jadwal, NomorPendaftaran $nomor): RedirectResponse
    {
        if (! $jadwal->isBuka(TahapJadwal::PENDAFTARAN)) {
            return back()->withErrors(['pendaftar' => 'Periode pendaftaran tidak dibuka — input manual tidak tersedia.']);
        }

        $validated = $request->validate([
            'nisn' => ['required', 'string', 'regex:/^\d{10}$/', 'unique:pendaftar,nisn', 'unique:users,nisn'],
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
            'dokumen_*' => ['nullable', 'file'],
        ], [
            'nisn.regex' => 'NISN harus 10 digit angka.',
            'nisn.unique' => 'NISN sudah terdaftar.',
        ]);

        $pilihan2 = $validated['pilihan_2'] ?? null;
        if ($pilihan2 && $pilihan2 === $validated['pilihan_1']) {
            return back()->withErrors(['pilihan_2' => 'Pilihan 2 harus berbeda dari pilihan 1.']);
        }

        $p = Pendaftar::create([
            ...collect([
                'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
                'agama', 'asal_sekolah', 'alamat', 'nik',
                'nama_orang_tua', 'pekerjaan_orang_tua', 'telepon_orang_tua',
            ])->mapWithKeys(fn ($f) => [$f => $validated[$f] ?? null])->all(),
            'nisn' => $validated['nisn'],
            'sumber' => 'manual',
            'status' => StatusPendaftar::MENUNGGU_VERIFIKASI,
            'dikirim_at' => now(),
        ]);

        $p->nomor_pendaftaran = $nomor->generate();
        $p->save();

        PendaftarPilihan::create(['pendaftar_id' => $p->id, 'jurusan_id' => $validated['pilihan_1'], 'prioritas' => 1]);
        if (! empty($pilihan2)) {
            PendaftarPilihan::create(['pendaftar_id' => $p->id, 'jurusan_id' => $pilihan2, 'prioritas' => 2]);
        }

        // Dokumen opsional (UC-08): file diunggah langsung dari admin
        $dok = app(DokumenService::class);
        foreach (JenisDokumen::cases() as $jenis) {
            $file = $request->file('dokumen_'.$jenis->value);
            if ($file && $file->isValid()) {
                try {
                    $dok->simpan($p, $jenis, $file);
                } catch (DokumenInvalid $e) {
                    return back()->withInput()->withErrors(['dokumen_'.$jenis->value => $e->getMessage()]);
                }
            }
        }

        return redirect()->route('admin.pendaftar.index')
            ->with('status', 'Pendaftar manual tersimpan. Nomor: '.$p->nomor_pendaftaran);
    }
}
