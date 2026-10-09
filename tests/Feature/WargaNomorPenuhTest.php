<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Warga;
use App\Support\ActiveRt;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class WargaNomorPenuhTest extends TestCase
{
    use RefreshDatabase;

    private const KK = '3201234567890001';

    private const NIK = '3276012345678901';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function rtUtama(): Rt
    {
        return Rt::query()->where('slug', 'rt-05')->firstOrFail();
    }

    private function loginSebagai(string $username): void
    {
        $this->post('/login', [
            'username' => $username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        if ($username === 'admin') {
            session([ActiveRt::SESSION_KEY => $this->rtUtama()->id]);
        }
    }

    /**
     * @return array{keluarga: Keluarga, warga: Warga}
     */
    private function buatKeluargaDenganKepala(?Rt $rt = null): array
    {
        $rt = $rt ?? $this->rtUtama();

        $keluarga = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => self::KK,
            'alamat' => 'Jl. Merdeka No. 1',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);

        $warga = Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga->id,
            'nik' => self::NIK,
            'nama' => 'Budi Santoso',
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        return ['keluarga' => $keluarga, 'warga' => $warga];
    }

    public function test_ketua_rt_mendapat_nomor_kk_dan_nik_lengkap(): void
    {
        $this->loginSebagai('ketua');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $response = $this->getJson(route('warga.nomor-penuh', $keluarga));

        $response->assertOk();
        $response->assertJsonPath('no_kk', self::KK);
        $response->assertJsonFragment([
            'nama' => 'Budi Santoso',
            'nik' => self::NIK,
        ]);
    }

    public function test_pengurus_mendapat_403(): void
    {
        $this->loginSebagai('pengurus');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $this->getJson(route('warga.nomor-penuh', $keluarga))->assertForbidden();
    }

    public function test_keluarga_rt_lain_menjawab_404(): void
    {
        $rtLain = Rt::query()->create([
            'nama' => 'RT 10',
            'rw' => '01',
            'kelurahan' => 'Test',
            'kecamatan' => 'Test',
            'kota' => 'Test',
            'slug' => 'rt-10',
            'publik_aktif' => true,
        ]);

        ['keluarga' => $keluargaLain] = $this->buatKeluargaDenganKepala($rtLain);

        $this->loginSebagai('ketua');

        $this->getJson(route('warga.nomor-penuh', $keluargaLain))->assertNotFound();
    }

    public function test_pemanggilan_berhasil_mencatat_satu_activity_log(): void
    {
        $this->loginSebagai('ketua');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $this->assertDatabaseCount('activity_log', 0);

        $this->getJson(route('warga.nomor-penuh', $keluarga))->assertOk();

        $this->assertDatabaseCount('activity_log', 1);

        $log = Activity::query()->first();
        $this->assertNotNull($log);
        $this->assertSame('nomor_penuh_dibuka', $log->event);

        $properties = $log->properties->toArray();
        $this->assertArrayHasKey('nama_pengguna', $properties);
        $this->assertArrayHasKey('nama_kepala_keluarga', $properties);
        $this->assertArrayHasKey('jumlah_nik_dibuka', $properties);
        $this->assertSame('Budi Santoso', $properties['nama_kepala_keluarga']);
        $this->assertSame(1, $properties['jumlah_nik_dibuka']);

        $encoded = json_encode($log->toArray());
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString(self::KK, $encoded);
        $this->assertStringNotContainsString(self::NIK, $encoded);
    }

    public function test_halaman_detail_menampilkan_tampilan_tersamar_sebelum_tombol(): void
    {
        $this->loginSebagai('ketua');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $response = $this->get(route('warga.show', $keluarga));

        $response->assertOk();
        $response->assertSee('Tampilkan nomor penuh', false);
        $response->assertDontSee(self::KK, false);
        $response->assertDontSee(self::NIK, false);
        $response->assertSee('3276........8901', false);
    }

    public function test_pengurus_tidak_melihat_tombol_nomor_penuh(): void
    {
        $this->loginSebagai('pengurus');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $response = $this->get(route('warga.show', $keluarga));

        $response->assertOk();
        $response->assertDontSee('Tampilkan nomor penuh', false);
    }
}
