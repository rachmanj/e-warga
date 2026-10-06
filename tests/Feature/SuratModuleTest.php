<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Surat;
use App\Models\SuratJenis;
use App\Models\Warga;
use App\Support\ActiveRt;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuratJenisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratModuleTest extends TestCase
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
     * @return array{keluarga: Keluarga, warga: Warga, jenis: SuratJenis}
     */
    private function siapkanPengajuan(): array
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

        $jenis = SuratJenis::query()->where('kode', 'SKD')->firstOrFail();

        return compact('keluarga', 'warga', 'jenis');
    }

    private function ajukanSurat(array $data): Surat
    {
        $response = $this->post(route('surat.store'), [
            'surat_jenis_id' => $data['jenis']->id,
            'keluarga_id' => $data['keluarga']->id,
            'warga_id' => $data['warga']->id,
            'keperluan' => 'Keperluan administrasi',
        ]);

        $response->assertRedirect();

        return Surat::query()->latest('id')->firstOrFail();
    }

    public function test_ajukan_dan_terbitkan_nomor_urut_001(): void
    {
        Carbon::setTestNow('2026-06-15');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();
        $surat = $this->ajukanSurat($data);

        $this->post('/logout');

        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat))->assertRedirect();

        $surat->refresh();
        $rt = $this->rtUtama();

        $this->assertSame('terbit', $surat->status);
        $this->assertSame('001/SKD/'.$rt->nama.'/'.$rt->rw.'/2026', $surat->nomor_lengkap);
        $this->assertSame(1, $surat->nomor_urut);
    }

    public function test_surat_kedua_nomor_002_pada_tahun_sama(): void
    {
        Carbon::setTestNow('2026-06-15');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();

        $pertama = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $pertama));

        $this->post('/logout');
        $this->loginSebagai('sekretaris');
        $kedua = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $kedua));

        $kedua->refresh();
        $this->assertSame(2, $kedua->nomor_urut);
        $this->assertStringStartsWith('002/SKD/', $kedua->nomor_lengkap ?? '');
    }

    public function test_tahun_baru_nomor_urut_reset_001(): void
    {
        Carbon::setTestNow('2026-12-20');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();
        $surat2026 = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat2026));

        Carbon::setTestNow('2027-01-10');
        $this->post('/logout');
        $this->loginSebagai('sekretaris');
        $surat2027 = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat2027));

        $surat2027->refresh();
        $rt = $this->rtUtama();
        $this->assertSame('001/SKD/'.$rt->nama.'/'.$rt->rw.'/2027', $surat2027->nomor_lengkap);
        $this->assertSame(1, $surat2027->nomor_urut);
    }

    public function test_pengurus_tidak_bisa_mengajukan_surat(): void
    {
        $this->loginSebagai('pengurus');
        $data = $this->siapkanPengajuan();

        $this->post(route('surat.store'), [
            'surat_jenis_id' => $data['jenis']->id,
            'keluarga_id' => $data['keluarga']->id,
            'warga_id' => $data['warga']->id,
            'keperluan' => 'Test',
        ])->assertForbidden();
    }

    public function test_sekretaris_tidak_bisa_menerbitkan_surat(): void
    {
        Carbon::setTestNow('2026-06-15');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();
        $surat = $this->ajukanSurat($data);

        $this->post(route('surat.terbitkan', $surat))->assertForbidden();
    }

    public function test_halaman_verifikasi_publik_tanpa_data_sensitif(): void
    {
        Carbon::setTestNow('2026-06-15');
        Storage::fake('local');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();
        $surat = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat));
        $surat->refresh();

        $response = $this->get(route('verifikasi.show', $surat->kode_verifikasi));
        $response->assertOk();
        $response->assertSee($surat->nomor_lengkap, false);
        $response->assertDontSee(self::NIK, false);
        $response->assertDontSee(self::KK, false);
    }

    public function test_kode_verifikasi_palsu_404(): void
    {
        $this->get(route('verifikasi.show', 'KODESALAH1234'))->assertNotFound();
    }

    public function test_pdf_disimpan_di_storage_privat_bukan_public(): void
    {
        Carbon::setTestNow('2026-06-15');
        Storage::fake('local');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();
        $surat = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat));
        $surat->refresh();

        $this->assertNotNull($surat->file_path);
        Storage::disk('local')->assertExists($surat->file_path);
        $this->assertStringStartsWith('surat/2026/', $surat->file_path);
        $this->assertFileDoesNotExist(public_path($surat->file_path));
    }

    public function test_unduh_pdf_melalui_rute_200(): void
    {
        Carbon::setTestNow('2026-06-15');
        $this->loginSebagai('sekretaris');
        $data = $this->siapkanPengajuan();
        $surat = $this->ajukanSurat($data);
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat));
        $surat->refresh();

        $this->loginSebagai('sekretaris');
        $this->get(route('surat.pdf', $surat))
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
