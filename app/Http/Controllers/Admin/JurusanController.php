<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Kuota;
use App\Models\PendaftarPilihan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurusanController extends Controller
{
    public function index(): View
    {
        return view('admin.jurusan.index', [
            'jurusan' => Jurusan::with('kuota')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.jurusan.form', ['j' => new Jurusan(['aktif' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, null);

        $j = Jurusan::create([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'rombel' => $data['rombel'],
            'aktif' => $data['aktif'] ?? true,
        ]);
        $j->kuota()->create([
            'kuota' => $data['kuota'],
            'cadangan' => $this->cadanganAtauDefault($data),
        ]);

        return redirect()->route('admin.jurusan.index')->with('status', 'Jurusan disimpan.');
    }

    public function edit(Jurusan $jurusan): View
    {
        return view('admin.jurusan.form', ['j' => $jurusan]);
    }

    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        $data = $this->validasi($request, $jurusan);

        $jurusan->update([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'rombel' => $data['rombel'],
            'aktif' => $data['aktif'] ?? true,
        ]);
        $jurusan->kuota()->update([
            'kuota' => $data['kuota'],
            'cadangan' => $this->cadanganAtauDefault($data),
        ]);

        return redirect()->route('admin.jurusan.index')->with('status', 'Jurusan diperbarui.');
    }

    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        $adaPendaftar = PendaftarPilihan::where('jurusan_id', $jurusan->id)->exists();

        if ($adaPendaftar) {
            $jurusan->update(['aktif' => false]);

            return redirect()->route('admin.jurusan.index')
                ->with('status', 'Jurusan sudah punya pendaftar — dinonaktifkan, tidak dihapus.');
        }

        $jurusan->kuota()->delete();
        $jurusan->delete();

        return redirect()->route('admin.jurusan.index')->with('status', 'Jurusan dihapus.');
    }

    private function validasi(Request $request, ?Jurusan $j): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:10', 'alpha', Rule::unique('jurusan', 'kode')->ignore($j?->id)],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'rombel' => ['required', 'integer', 'min:1', 'max:20'],
            'kuota' => ['required', 'integer', 'min:0', 'max:2000'],
            'cadangan' => ['nullable', 'integer', 'min:0', 'max:500'],
            'aktif' => ['nullable', 'boolean'],
        ]);
    }

    private function cadanganAtauDefault(array $data): int
    {
        return filled($data['cadangan'] ?? null)
            ? (int) $data['cadangan']
            : (int) ceil(((int) $data['kuota']) * 0.1);
    }
}
