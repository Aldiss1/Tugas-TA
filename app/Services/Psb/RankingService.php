<?php

namespace App\Services\Psb;

use App\Enums\StatusPendaftar;
use App\Exceptions\AdaPendaftarTanpaNilai;
use App\Exceptions\PengumumanSudahTerbit;
use App\Models\Hasil;
use App\Models\Jurusan;
use App\Models\Kuota;
use App\Models\Pendaftar;
use App\Models\PendaftarPilihan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingService
{
    /**
     * Proses ranking & penetapan. Urutan (spec §5.2):
     * 1. Semua antrian prioritas 1 per jurusan: DITERIMA s.d. kuota,
     *    CADANGAN s.d. kuota+cadangan, sisanya ke antrian P2.
     * 2. Antrian P2 (urutan global) mengisi sisa kuota jurusan pilihan 2.
     * 3. Sisanya TIDAK_DITERIMA.
     * Tie-break: total DESC, nilai_rapor DESC, dikirim_at ASC, id ASC.
     *
     * @return array{diterima: int, cadangan: int, tidak_diterima: int, per_jurusan: array<string, array<string, int>>}
     */
    public function proses(User $by): array
    {
        if (app(PengumumanService::class)->sudahTerbit()) {
            throw PengumumanSudahTerbit::make();
        }

        $pool = [
            StatusPendaftar::LULUS_VERIFIKASI,
            StatusPendaftar::DITERIMA,
            StatusPendaftar::CADANGAN,
            StatusPendaftar::TIDAK_DITERIMA,
        ];

        $tanpaNilai = Pendaftar::whereIn('status', $pool)
            ->whereDoesntHave('nilai')
            ->pluck('id');

        if ($tanpaNilai->isNotEmpty()) {
            throw new AdaPendaftarTanpaNilai($tanpaNilai->all());
        }

        return DB::transaction(function () {
            // Reset hasil lama (proses ulang sebelum publish)
            Hasil::query()->delete();
            Kuota::query()->update(['terisi' => 0]);

            $now = now();
            $sisaKuota = [];   // jurusan_id => slot DITERIMA tersisa
            $rankTerisi = [];  // jurusan_id => jumlah baris hasil (diterima+cadangan)
            $antrianP2 = [];
            $r = ['diterima' => 0, 'cadangan' => 0, 'tidak_diterima' => 0, 'per_jurusan' => []];
            foreach (Jurusan::with('kuota')->where('aktif', true)->orderBy('id')->get() as $j) {
                $kuota = (int) ($j->kuota->kuota ?? 0);
                $cadangan = (int) ($j->kuota->cadangan ?? 0);
                $sisaKuota[$j->id] = $kuota;
                $rankTerisi[$j->id] = 0;

                foreach ($this->antrianPilihan(1, $j->id) as $pl) {
                    $p = $pl->pendaftar;
                    $rank = ++$rankTerisi[$j->id];
                    if ($rank <= $kuota) {
                        $this->catat($p, $j->id, $rank, 'diterima', 1, $now);
                        $sisaKuota[$j->id]--;
                        $r['diterima']++;
                    } elseif ($rank <= $kuota + $cadangan) {
                        $this->catat($p, $j->id, $rank, 'cadangan', 1, $now);
                        $r['cadangan']++;
                    } else {
                        $antrianP2[] = $p;
                    }
                }

                $r['per_jurusan'][$j->kode] = [
                    'kuota' => $kuota,
                    'terisi' => $kuota - max(0, $sisaKuota[$j->id]),
                    'cadangan' => $cadangan,
                ];
            }

            // Tahap 2: antrian P2 mengisi sisa kuota
            foreach ($antrianP2 as $p) {
                $pl2 = $p->pilihan()->where('prioritas', 2)->first();

                if ($pl2 && ($sisaKuota[$pl2->jurusan_id] ?? 0) > 0) {
                    $j2 = Jurusan::find($pl2->jurusan_id);
                    $sisaKuota[$pl2->jurusan_id]--;
                    $rank = ++$rankTerisi[$pl2->jurusan_id];
                    $this->catat($p, $j2->id, $rank, 'diterima', 2, $now);
                    $r['diterima']++;
                    if (isset($r['per_jurusan'][$j2->kode])) {
                        $r['per_jurusan'][$j2->kode]['terisi']++;
                    }
                } else {
                    $this->catat($p, null, null, 'tidak_diterima', null, $now);
                    $r['tidak_diterima']++;
                }
            }

            // Sinkronkan kuota.terisi
            foreach (Jurusan::all() as $j) {
                $j->kuota?->update(['terisi' => max(0, (int) $j->kuota->kuota - ($sisaKuota[$j->id] ?? 0))]);
            }

            app(AuditService::class)->catat(null, 'psb', 'proses_ranking', [
                'diterima' => $r['diterima'],
                'cadangan' => $r['cadangan'],
                'tidak_diterima' => $r['tidak_diterima'],
            ]);

            return $r;
        });
    }

    /**
     * Antrian pendaftar untuk satu jurusan & prioritas, terurut tie-break.
     */
    private function antrianPilihan(int $prioritas, int $jurusanId): Collection
    {
        return PendaftarPilihan::where('prioritas', $prioritas)
            ->where('jurusan_id', $jurusanId)
            ->with(['pendaftar.nilai'])
            ->get()
            ->filter(fn ($pl) => $pl->pendaftar
                && in_array($pl->pendaftar->status, [
                    StatusPendaftar::LULUS_VERIFIKASI,
                    StatusPendaftar::DITERIMA,
                    StatusPendaftar::CADANGAN,
                    StatusPendaftar::TIDAK_DITERIMA,
                ], true)
                && $pl->pendaftar->nilai)
            ->sortBy(
                function ($pl) {
                    $n = $pl->pendaftar->nilai;

                    return [
                        (float) $n->total,
                        (float) $n->nilai_rapor,
                        -Carbon::parse($pl->pendaftar->dikirim_at ?? now())->getTimestampMs(),
                        -$pl->pendaftar->id,
                    ];
                },
                SORT_REGULAR,
                true
            )
            ->values();
    }

    private function catat(Pendaftar $p, ?int $jurusanId, ?int $rank, string $status, ?int $via, $now): void
    {
        Hasil::create([
            'pendaftar_id' => $p->id,
            'jurusan_id' => $jurusanId,
            'perankingan' => $rank,
            'status_akhir' => $status,
            'via_pilihan' => $via,
            'diproses_at' => $now,
        ]);

        // Catatan: status pendaftar baru diterapkan saat pengumuman diterbitkan
        // (PengumumanService::terbitkan) agar hasil tidak bocor sebelum pengumuman.
    }
}
