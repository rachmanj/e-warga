<?php

namespace Tests\Feature;

use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\IuranTarif;
use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use App\Models\Keluarga;
use App\Models\Kwitansi;
use App\Models\Rt;
use App\Models\User;
use App\Services\IuranService;
use App\Services\KasService;
use Database\Seeders\KasKategoriSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class IuranKasModuleTest extends TestCase
{
    use RefreshDatabase;

    private KasService $kasService;

    private IuranService $iuranService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(KasKategoriSeeder::class);

        $this->kasService = app(KasService::class);
        $this->iuranService = app(IuranService::class);
    }

    private function rtUtama(): Rt
    {
        return Rt::query()->where('slug', 'rt-05')->firstOrFail();
    }

    private function bendahara(): User
    {
        return User::query()->where('username', 'bendahara')->firstOrFail();
    }

    private function buatKeluargaAktif(?Rt $rt = null): Keluarga
    {
        $rt = $rt ?? $this->rtUtama();

        return Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => '3201234567890'.random_int(100, 999),
            'alamat' => 'Jl. Test',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);
    }

    private function buatJenisIuran(?Rt $rt = null, float $default = 50000): IuranJenis
    {
        $rt = $rt ?? $this->rtUtama();

        return IuranJenis::query()->create([
            'tenant_id' => $rt->id,
            'nama' => 'Iuran Bulanan',
            'nominal_default' => $default,
            'periode' => 'bulanan',
            'aktif' => true,
        ]);
    }

    public function test_saldo_akhir_sesuai_rumus_dan_pos_tunai_bank_terpisah(): void
    {
        $rt = $this->rtUtama();
        $tahun = 2025;

        KasSaldoAwal::query()->create([
            'tenant_id' => $rt->id,
            'tahun' => $tahun,
            'pos' => 'tunai',
            'jumlah' => '100000.00',
        ]);
        KasSaldoAwal::query()->create([
            'tenant_id' => $rt->id,
            'tahun' => $tahun,
            'pos' => 'bank',
            'jumlah' => '200000.00',
        ]);

        KasTransaksi::query()->create([
            'tenant_id' => $rt->id,
            'tanggal' => '2025-03-15',
            'jenis' => 'masuk',
            'pos' => 'tunai',
            'uraian' => 'Masuk tunai',
            'jumlah' => '25000.00',
        ]);
        KasTransaksi::query()->create([
            'tenant_id' => $rt->id,
            'tanggal' => '2025-04-10',
            'jenis' => 'keluar',
            'pos' => 'tunai',
            'uraian' => 'Keluar tunai',
            'jumlah' => '10000.00',
        ]);
        KasTransaksi::query()->create([
            'tenant_id' => $rt->id,
            'tanggal' => '2025-05-01',
            'jenis' => 'masuk',
            'pos' => 'bank',
            'uraian' => 'Masuk bank',
            'jumlah' => '50000.00',
        ]);

        $ringkasanTunai = $this->kasService->ringkasan($rt->id, $tahun, 'tunai');
        $this->assertSame('100000.00', $ringkasanTunai['saldo_awal']);
        $this->assertSame('25000.00', $ringkasanTunai['total_masuk']);
        $this->assertSame('10000.00', $ringkasanTunai['total_keluar']);
        $this->assertSame('115000.00', $ringkasanTunai['saldo_akhir']);
        $this->assertSame($ringkasanTunai['saldo_akhir'], $this->kasService->saldoAkhir($rt->id, $tahun, 'tunai'));

        $ringkasanBank = $this->kasService->ringkasan($rt->id, $tahun, 'bank');
        $this->assertSame('250000.00', $ringkasanBank['saldo_akhir']);
    }

    public function test_rekap_bulanan_saldo_kumulatif_benar(): void
    {
        $rt = $this->rtUtama();
        $tahun = 2025;

        KasSaldoAwal::query()->create([
            'tenant_id' => $rt->id,
            'tahun' => $tahun,
            'pos' => 'tunai',
            'jumlah' => '1000.00',
        ]);

        KasTransaksi::query()->create([
            'tenant_id' => $rt->id,
            'tanggal' => '2025-01-20',
            'jenis' => 'masuk',
            'pos' => 'tunai',
            'uraian' => 'Jan',
            'jumlah' => '100.00',
        ]);
        KasTransaksi::query()->create([
            'tenant_id' => $rt->id,
            'tanggal' => '2025-02-05',
            'jenis' => 'keluar',
            'pos' => 'tunai',
            'uraian' => 'Feb',
            'jumlah' => '30.00',
        ]);

        $rekap = $this->kasService->rekapBulanan($rt->id, $tahun, 'tunai');

        $this->assertCount(12, $rekap);
        $this->assertSame('1100.00', $rekap[0]['saldo']);
        $this->assertSame('1070.00', $rekap[1]['saldo']);
        $this->assertSame('1070.00', $rekap[11]['saldo']);
    }

    public function test_tarif_khusus_keluarga_menggantikan_nominal_default(): void
    {
        $keluarga = $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran(default: 50000);

        IuranTarif::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'iuran_jenis_id' => $jenis->id,
            'keluarga_id' => $keluarga->id,
            'nominal' => '35000.00',
        ]);

        $this->assertSame('35000.00', $this->iuranService->tarifEfektif($keluarga, $jenis));
    }

    public function test_buat_tagihan_dua_kali_idempoten(): void
    {
        $this->buatKeluargaAktif();
        $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran();

        $pertama = $this->iuranService->buatTagihan($jenis, '2025-01');
        $kedua = $this->iuranService->buatTagihan($jenis, '2025-01');

        $this->assertSame(2, $pertama['dibuat']);
        $this->assertSame(0, $pertama['dilewati']);
        $this->assertSame(0, $kedua['dibuat']);
        $this->assertSame(2, $kedua['dilewati']);
        $this->assertSame(2, IuranTagihan::query()->where('periode', '2025-01')->count());
    }

    public function test_pembayaran_sebagian_dan_pelunasan_memperbarui_status(): void
    {
        $keluarga = $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran(default: 100000);
        $bendahara = $this->bendahara();

        $tagihan = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-06',
            'nominal' => '100000.00',
            'status' => 'belum',
        ]);

        $this->iuranService->catatPembayaran($tagihan, '2025-06-10', '40000.00', 'tunai', $bendahara->id);
        $tagihan->refresh();
        $this->assertSame('sebagian', $tagihan->status);

        $this->iuranService->catatPembayaran($tagihan, '2025-06-15', '60000.00', 'transfer', $bendahara->id);
        $tagihan->refresh();
        $this->assertSame('lunas', $tagihan->status);
    }

    public function test_catat_pembayaran_membuat_satu_kas_masuk_dengan_pos_sesuai_metode(): void
    {
        $keluarga = $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran();
        $bendahara = $this->bendahara();

        $tagihan = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-07',
            'nominal' => '50000.00',
            'status' => 'belum',
        ]);

        $this->iuranService->catatPembayaran($tagihan, '2025-07-01', '50000.00', 'tunai', $bendahara->id);

        $kas = KasTransaksi::query()->where('iuran_pembayaran_id', '!=', null)->get();
        $this->assertCount(1, $kas);
        $this->assertSame('masuk', $kas->first()->jenis);
        $this->assertSame('tunai', $kas->first()->pos);

        $tagihan2 = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-08',
            'nominal' => '50000.00',
            'status' => 'belum',
        ]);

        $this->iuranService->catatPembayaran($tagihan2, '2025-08-01', '50000.00', 'lainnya', $bendahara->id);
        $kasBank = KasTransaksi::query()
            ->where('iuran_pembayaran_id', IuranPembayaran::query()->latest('id')->value('id'))
            ->first();
        $this->assertSame('bank', $kasBank->pos);
    }

    public function test_kwitansi_bernomor_urut_per_tahun(): void
    {
        $keluarga = $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran();
        $bendahara = $this->bendahara();

        $tagihan1 = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-09',
            'nominal' => '10000.00',
            'status' => 'belum',
        ]);
        $tagihan2 = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-10',
            'nominal' => '10000.00',
            'status' => 'belum',
        ]);

        $this->iuranService->catatPembayaran($tagihan1, '2025-09-01', '10000.00', 'tunai', $bendahara->id);
        $this->iuranService->catatPembayaran($tagihan2, '2025-09-05', '10000.00', 'tunai', $bendahara->id);

        $nomor = Kwitansi::query()->orderBy('id')->pluck('nomor')->all();
        $this->assertSame(['KW20250001', 'KW20250002'], $nomor);
    }

    public function test_pembayaran_melebihi_sisa_ditolak(): void
    {
        $keluarga = $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran();
        $bendahara = $this->bendahara();

        $tagihan = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-11',
            'nominal' => '50000.00',
            'status' => 'belum',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->iuranService->catatPembayaran($tagihan, '2025-11-01', '50001.00', 'tunai', $bendahara->id);
    }

    public function test_hapus_pembayaran_menghapus_kas_dan_kwitansi_lalu_mengembalikan_status(): void
    {
        $keluarga = $this->buatKeluargaAktif();
        $jenis = $this->buatJenisIuran(default: 100000);
        $bendahara = $this->bendahara();

        $tagihan = IuranTagihan::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'iuran_jenis_id' => $jenis->id,
            'periode' => '2025-12',
            'nominal' => '100000.00',
            'status' => 'belum',
        ]);

        $pembayaran = $this->iuranService->catatPembayaran($tagihan, '2025-12-01', '100000.00', 'tunai', $bendahara->id);
        $tagihan->refresh();
        $this->assertSame('lunas', $tagihan->status);
        $this->assertDatabaseCount('kwitansi', 1);
        $this->assertSame(1, KasTransaksi::query()->whereNotNull('iuran_pembayaran_id')->count());

        $this->iuranService->hapusPembayaran($pembayaran);

        $tagihan->refresh();
        $this->assertSame('belum', $tagihan->status);
        $this->assertDatabaseCount('kwitansi', 0);
        $this->assertSame(0, KasTransaksi::query()->whereNotNull('iuran_pembayaran_id')->count());
    }

    public function test_tagihan_rt_lain_tidak_terlihat_dengan_scope_tenant(): void
    {
        $rtLain = Rt::query()->create([
            'nama' => 'RT 99',
            'rw' => '01',
            'kelurahan' => 'Test',
            'kecamatan' => 'Test',
            'kota' => 'Test',
            'slug' => 'rt-99',
        ]);

        $keluargaLain = $this->buatKeluargaAktif($rtLain);
        $jenisLain = $this->buatJenisIuran($rtLain);

        $tagihanLain = IuranTagihan::query()->create([
            'tenant_id' => $rtLain->id,
            'keluarga_id' => $keluargaLain->id,
            'iuran_jenis_id' => $jenisLain->id,
            'periode' => '2025-01',
            'nominal' => '10000.00',
            'status' => 'belum',
        ]);

        $this->actingAs($this->bendahara());

        $ids = IuranTagihan::query()->pluck('id')->all();
        $this->assertNotContains($tagihanLain->id, $ids);
    }
}
