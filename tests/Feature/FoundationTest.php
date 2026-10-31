<?php

namespace Tests\Feature;

use App\Enums\TahapJadwal;
use App\Models\JadwalPsb;
use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeder_membuat_7_jurusan_resmi_dengan_kuota_rombel_x_36_dan_cadangan_ceil_10(): void
    {
        $j = Jurusan::all();
        $this->assertSame(7, $j->count());
        $this->assertEqualsCanonicalizing(
            ['TKJ', 'RPL', 'ANM', 'BCS', 'TAV', 'TEI', 'MKA'],
            $j->pluck('kode')->all()
        );

        $tkj = Jurusan::where('kode', 'TKJ')->first();
        $this->assertSame(108, $tkj->kuota->kuota); // 3 rombel x 36
        $this->assertSame(11, $tkj->kuota->cadangan); // ceil(108 x 0.1)
    }

    public function test_nisn_tidak_boleh_duplikat_di_level_database(): void
    {
        User::factory()->create(['nisn' => '1111111111']);
        User::factory()->create(['nisn' => '2222222222']);

        $this->expectException(QueryException::class);
        User::factory()->create(['nisn' => '1111111111']);
    }

    public function test_5_baris_jadwal_psb_dengan_tahap_unique_dibuat_seeder(): void
    {
        $this->assertEqualsCanonicalizing(
            ['pendaftaran', 'verifikasi', 'perbaikan', 'seleksi', 'pengumuman'],
            JadwalPsb::pluck('tahap')
                ->map(fn ($t) => $t instanceof TahapJadwal ? $t->value : (string) $t)
                ->all()
        );
    }
}
