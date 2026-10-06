<?php

namespace Tests\Feature;

use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\KasSaldoAwal;
use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\User;
use App\Models\Warga;
use App\Services\IuranService;
use App\Services\KasService;
use App\Support\ActiveRt;
use App\Support\FormatUang;
use Carbon\Carbon;
use Database\Seeders\KasKategoriSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuratJenisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardModuleTest extends TestCase
{
    use RefreshDatabase;

    private KasService $kasService;

    private IuranService $iuranService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(KasKategoriSeeder::class);
        $this->seed(SuratJenisSeeder::class);

        $this->kasService = app(KasService::class);
        $this->iuranService = app(IuranService::class);

        Carbon::setTestNow('2025-10-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    public function test_dashboard_menampilkan_metrik_utama_dan_grafik_enam_bulan(): void
    {
        $rt = $this->rtUtama();
        $tahun = 2025;

        $keluarga1 = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => '3201234567890001',
            'alamat' => 'Jl. A',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);
        $keluarga2 = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => '3201234567890002',
            'alamat' => 'Jl. B',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);

        Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga1->id,
            'nik' => '3276012345678901',
            'nama' => 'Budi',
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
        Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga1->id,
            'nik' => '3276012345678902',
            'nama' => 'Siti',
            'hubungan' => 'istri',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
        ]);
        Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga2->id,
            'nik' => '3276012345678903',
            'nama' => 'Andi',
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $jenis = IuranJenis::query()->create([
            'tenant_id' => $rt->id,
            'nama' => 'Iuran Warga',
            'nominal_default' => 100000,
            'periode' => 'bulanan',
            'aktif' => true,
        ]);

        $tagihanBelum = IuranTagihan::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga1->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-10',
            'nominal' => '100000.00',
            'status' => 'belum',
        ]);

        $tagihanSebagian = IuranTagihan::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga2->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-09',
            'nominal' => '100000.00',
            'status' => 'sebagian',
        ]);

        IuranPembayaran::query()->create([
            'tenant_id' => $rt->id,
            'iuran_tagihan_id' => $tagihanSebagian->id,
            'tanggal' => '2025-09-10',
            'jumlah' => '40000.00',
            'metode' => 'tunai',
            'dicatat_oleh' => User::query()->where('username', 'bendahara')->firstOrFail()->id,
        ]);

        KasSaldoAwal::query()->create([
            'tenant_id' => $rt->id,
            'tahun' => $tahun,
            'pos' => 'tunai',
            'jumlah' => '500000.00',
        ]);
        KasSaldoAwal::query()->create([
            'tenant_id' => $rt->id,
            'tahun' => $tahun,
            'pos' => 'bank',
            'jumlah' => '750000.00',
        ]);

        $ringkasanTunggakan = $this->iuranService->ringkasanPeriode(
            $rt->id,
            ['2025-09', '2025-10'],
            null,
            ['belum', 'sebagian']
        );

        $saldoTunai = $this->kasService->ringkasan($rt->id, $tahun, 'tunai')['saldo_akhir'];
        $saldoBank = $this->kasService->ringkasan($rt->id, $tahun, 'bank')['saldo_akhir'];

        $this->loginSebagai('bendahara');

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('2 keluarga aktif', $html);
        $this->assertStringContainsString(FormatUang::ringkas($ringkasanTunggakan['total_tunggakan']), $html);
        $this->assertStringContainsString(FormatUang::penuh($saldoTunai), $html);
        $this->assertStringContainsString(FormatUang::penuh($saldoBank), $html);

        foreach (['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_pengurus_melihat_dashboard_tanpa_tombol_tambah_warga(): void
    {
        $this->loginSebagai('pengurus');

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $this->assertStringNotContainsString(route('warga.create'), $response->getContent());
        $this->assertStringNotContainsString('Tambah Warga', $response->getContent());
    }

    public function test_superadmin_tanpa_rt_aktif_diarahkan_ke_pemilihan_rt(): void
    {
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('rt-aktif.index'));
    }
}
