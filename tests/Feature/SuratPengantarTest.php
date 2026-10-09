<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Surat;
use App\Models\SuratJenis;
use App\Models\Warga;
use App\Services\SuratService;
use App\Support\ActiveRt;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuratJenisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratPengantarTest extends TestCase
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
     * @param  array<string, mixed>  $wargaOverrides
     * @param  list<string>  $keperluanSp
     */
    private function terbitkanSuratPengantar(array $wargaOverrides = [], array $keperluanSp = ['ktp', 'skck']): Surat
    {
        Carbon::setTestNow('2026-06-15');
        Storage::fake('local');

        $rt = $this->rtUtama();
        $keluarga = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => self::KK,
            'alamat' => 'Jl. Merdeka No. 1',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);

        $warga = Warga::query()->create(array_merge([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga->id,
            'nik' => self::NIK,
            'nama' => 'Budi Santoso',
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1966-06-06',
            'tempat_lahir' => 'Balikpapan',
            'pendidikan' => 'SMA',
            'agama' => 'Islam',
            'golongan_darah' => 'O',
            'kewarganegaraan' => 'WNI',
            'status' => 'aktif',
        ], $wargaOverrides));

        $jenis = SuratJenis::query()->where('kode', 'SP')->firstOrFail();

        $this->loginSebagai('sekretaris');
        $this->post(route('surat.store'), [
            'surat_jenis_id' => $jenis->id,
            'keluarga_id' => $keluarga->id,
            'warga_id' => $warga->id,
            'keperluan_sp' => $keperluanSp,
        ])->assertRedirect();

        $surat = Surat::query()->latest('id')->firstOrFail();
        $this->post('/logout');
        $this->loginSebagai('ketua');
        $this->post(route('surat.terbitkan', $surat))->assertRedirect();

        return $surat->fresh(['jenis', 'warga', 'keluarga']);
    }

    private function isiPdf(Surat $surat): string
    {
        $path = $surat->file_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        return Storage::disk('local')->get($path);
    }

    public function test_sp_pdf_memuat_centang_jenis_kelamin_agama_dan_golongan_darah(): void
    {
        $surat = $this->terbitkanSuratPengantar();
        $pdf = $this->isiPdf($surat);
        $rt = $this->rtUtama();
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $rt);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString(self::NIK, $html);
        $this->assertStringContainsString('Sudah terdaftar dalam Kartu Keluarga', $html);
        $this->assertStringContainsString(self::KK, $html);
        $this->assertStringContainsString('<strong>✓ Laki-laki</strong>', $html);
        $this->assertStringContainsString('<strong>✓ Islam</strong>', $html);
        $this->assertStringContainsString('<strong>✓ O</strong>', $html);
    }

    public function test_sp_keperluan_terpilih_ditandai_yang_lain_tidak(): void
    {
        $surat = $this->terbitkanSuratPengantar([], ['ktp']);
        $rt = $this->rtUtama();
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $rt);

        $this->assertStringContainsString('<strong>✓ Kartu Tanda Penduduk (KTP)</strong>', $html);
        $this->assertStringNotContainsString('<strong>✓ Kartu Keluarga (KK)</strong>', $html);
        $this->assertStringContainsString('2. Kartu Keluarga (KK)', $html);
    }

    public function test_sp_validasi_menolak_pengajuan_tanpa_keperluan(): void
    {
        $rt = $this->rtUtama();
        $keluarga = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => self::KK,
            'alamat' => 'Jl. Uji',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);
        $warga = Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga->id,
            'nik' => self::NIK,
            'nama' => 'Test',
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
        $jenis = SuratJenis::query()->where('kode', 'SP')->firstOrFail();

        $this->loginSebagai('sekretaris');
        $this->from(route('surat.create'))
            ->post(route('surat.store'), [
                'surat_jenis_id' => $jenis->id,
                'keluarga_id' => $keluarga->id,
                'warga_id' => $warga->id,
            ])
            ->assertSessionHasErrors('keperluan_sp');
    }

    public function test_sp_pemetaan_pendidikan_sma_menandai_slta(): void
    {
        $surat = $this->terbitkanSuratPengantar(['pendidikan' => 'SMA']);
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $this->rtUtama());

        $this->assertStringContainsString('<strong>✓ SLTA</strong>', $html);
    }

    public function test_sp_pemetaan_pendidikan_d3_menandai_universitas(): void
    {
        $surat = $this->terbitkanSuratPengantar(['pendidikan' => 'D3']);
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $this->rtUtama());

        $this->assertStringContainsString('<strong>✓ Universitas atau Akademi</strong>', $html);
    }

    public function test_sp_pemetaan_pendidikan_s2_menandai_universitas(): void
    {
        $surat = $this->terbitkanSuratPengantar(['pendidikan' => 'S2']);
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $this->rtUtama());

        $this->assertStringContainsString('<strong>✓ Universitas atau Akademi</strong>', $html);
    }

    public function test_sp_template_memakai_kalimat_pembuka_dan_judul_pertimbangan_resmi(): void
    {
        $surat = $this->terbitkanSuratPengantar();
        $rt = $this->rtUtama();
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $rt);

        $this->assertStringContainsString(
            'Bersama ini kami mengantarkan warga '.$rt->nama.' yang disebutkan dibawah ini :',
            $html
        );
        $this->assertStringNotContainsString('Yang bertanda tangan di bawah ini', $html);
        $this->assertStringContainsString(
            'Sebagai pertimbangan diberitahukan bahwa yang bersangkutan :',
            $html
        );
        $this->assertStringNotContainsString('Pertimbangan Kartu Keluarga', $html);
    }

    public function test_sp_nomor_surat_memakai_tahun_dua_digit(): void
    {
        $surat = $this->terbitkanSuratPengantar();

        $this->assertSame('001/GSI-32/26', $surat->nomor_lengkap);
        $this->assertStringNotContainsString('/2026', $surat->nomor_lengkap ?? '');
    }

    public function test_sp_keperluan_keterangan_domisili_ditandai(): void
    {
        $surat = $this->terbitkanSuratPengantar([], ['keterangan_domisili']);
        $html = $this->app->make(SuratService::class)->renderTemplate($surat, $this->rtUtama());

        $this->assertStringContainsString('<strong>✓ Keterangan Domisili</strong>', $html);
    }
}
