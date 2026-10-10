<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Surat;
use App\Models\SuratJenis;
use App\Models\SuratNomorCounter;
use App\Models\Warga;
use App\Support\ActiveRt;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuratJenisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratNomorGlobalTest extends TestCase
{
    use RefreshDatabase;

    private const KK = '3201234567890001';

    private const NIK = '3276012345678901';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SuratJenisSeeder::class);
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
    private function siapkanWarga(): array
    {
        $rt = $this->rtUtama();
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

        return compact('keluarga', 'warga');
    }

    private function ajukanDanTerbitkan(Keluarga $keluarga, Warga $warga, SuratJenis $jenis): Surat
    {
        $this->loginSebagai('sekretaris');

        $this->post(route('surat.store'), [
            'surat_jenis_id' => $jenis->id,
            'keluarga_id' => $keluarga->id,
            'warga_id' => $warga->id,
            'keperluan' => 'Keperluan administrasi',
        ])->assertRedirect();

        $surat = Surat::query()->latest('id')->firstOrFail();

        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat))->assertRedirect();
        $this->post('/logout');

        return $surat->fresh();
    }

    public function test_dua_jenis_berbeda_mendapat_nomor_berurutan_global(): void
    {
        Carbon::setTestNow('2026-06-15');
        Storage::fake('local');

        $data = $this->siapkanWarga();
        $jenisSkd = SuratJenis::query()->where('kode', 'SKD')->firstOrFail();
        $jenisSku = SuratJenis::query()->where('kode', 'SKU')->firstOrFail();

        $pertama = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenisSkd);
        $kedua = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenisSku);

        $this->assertSame(1, $pertama->nomor_urut);
        $this->assertSame('001/GSI-32/26', $pertama->nomor_lengkap);
        $this->assertSame(2, $kedua->nomor_urut);
        $this->assertSame('002/GSI-32/26', $kedua->nomor_lengkap);
    }

    public function test_tahun_baru_memulai_nomor_dari_satu_lagi(): void
    {
        Carbon::setTestNow('2026-12-20');
        Storage::fake('local');

        $data = $this->siapkanWarga();
        $jenis = SuratJenis::query()->where('kode', 'SKD')->firstOrFail();

        $surat2026 = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenis);
        $this->assertSame(1, $surat2026->nomor_urut);

        Carbon::setTestNow('2027-01-10');
        $surat2027 = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenis);

        $this->assertSame(1, $surat2027->nomor_urut);
        $this->assertSame('001/GSI-32/27', $surat2027->nomor_lengkap);
    }

    public function test_setelah_surat_dihapus_nomor_tidak_dipakai_ulang(): void
    {
        Carbon::setTestNow('2026-06-15');
        Storage::fake('local');

        $data = $this->siapkanWarga();
        $jenis = SuratJenis::query()->where('kode', 'SKD')->firstOrFail();

        $pertama = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenis);
        $this->assertSame(1, $pertama->nomor_urut);

        $pertama->delete();

        $kedua = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenis);

        $this->assertSame(2, $kedua->nomor_urut);
        $this->assertSame('002/GSI-32/26', $kedua->nomor_lengkap);
    }

    public function test_perintah_set_nomor_terakhir_mengatur_penghitung_dan_surat_berikutnya(): void
    {
        Carbon::setTestNow('2026-06-15');
        Storage::fake('local');

        $rt = $this->rtUtama();

        $this->artisan('surat:set-nomor-terakhir', [
            '--tenant' => (string) $rt->id,
            '--tahun' => '2026',
            '--terakhir' => '15',
        ])->assertSuccessful();

        $counter = SuratNomorCounter::query()
            ->where('tenant_id', $rt->id)
            ->where('tahun', 2026)
            ->first();

        $this->assertNotNull($counter);
        $this->assertSame(15, $counter->nomor_terakhir);

        $data = $this->siapkanWarga();
        $jenis = SuratJenis::query()->where('kode', 'SKD')->firstOrFail();
        $surat = $this->ajukanDanTerbitkan($data['keluarga'], $data['warga'], $jenis);

        $this->assertSame(16, $surat->nomor_urut);
        $this->assertSame('016/GSI-32/26', $surat->nomor_lengkap);

        $counter->refresh();
        $this->assertSame(16, $counter->nomor_terakhir);
    }
}
