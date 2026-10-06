<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Warga;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportWargaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function buatRtImpor(): Rt
    {
        return Rt::query()->create([
            'nama' => 'RT 32',
            'slug' => 'rt-32-impor',
            'rw' => '01',
            'kelurahan' => 'Test',
            'kecamatan' => 'Test',
            'kota' => 'Test',
        ]);
    }

    /**
     * @return array{path: string, keluarga: int, warga: int}
     */
    private function buatCsvFixture(): array
    {
        $path = storage_path('framework/testing/rt32-import-fixture-'.uniqid('', true).'.csv');

        $header = 'baris,no_urut,no_kk,no_kk_16,rt,no_rumah,nama_kepala,no_hp,nama_anggota,nik_16,nik_asli,jk,tanggal_lahir,jumlah_anggota,tempat_lahir,gol_darah,grade,pendidikan,pekerjaan,agama,status_rumah,bantuan,warna,is_kepala,keluarga_index,keluarga_no_kk,keluarga_nama_kepala,keluarga_warna,keluarga_status_rumah';

        $rows = [
            '1,1,6471031707130010,6471031707130010,32,2,ARI MULYA,,ARI MULYA,9771032911840001,977103-291184-0001,L,1984-11-20,2,BALIKPAPAN,AB,,SMA,PEDAGANG,ISLAM,R/S,,,True,1,6471031707130010,ARI MULYA,,RUMAH SENDIRI',
            '2,,,,,,,,ENDAH PURNAMA SARI,6471045505820003,647104-550582-0003,P,1982-05-18,,BALIKPAPAN,O,,SMA,IRT,,,,,False,1,6471031707130010,ARI MULYA,,RUMAH SENDIRI',
            '3,2,6471041311130011,6471041311130011,32,4,HIJAU KELUARGA,,HIJAU KELUARGA,6471041210870001,,L,1987-10-12,1,BALIKPAPAN,,,,,ISLAM,,,,True,2,6471041311130011,HIJAU KELUARGA,hijau,RUMAH SEWA',
            '4,3,6471040911090052,6471040911090052,32,9,NIK INVALID,,NIK INVALID,123,123-short,A,1990-01-01,1,,,,,,ISLAM,,,,True,3,6471040911090052,NIK INVALID,,SEWA',
        ];

        File::put($path, $header."\n".implode("\n", $rows)."\n");

        return [
            'path' => $path,
            'keluarga' => 3,
            'warga' => 4,
        ];
    }

    public function test_dry_run_default_tidak_menulis_database(): void
    {
        $rt = $this->buatRtImpor();
        $fixture = $this->buatCsvFixture();

        $keluargaSebelum = Keluarga::query()->count();
        $wargaSebelum = Warga::query()->count();

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path'],
            '--tenant' => $rt->id,
        ])->assertSuccessful();

        $this->assertSame($keluargaSebelum, Keluarga::query()->count());
        $this->assertSame($wargaSebelum, Warga::query()->count());

        File::delete($fixture['path']);
    }

    public function test_execute_tanpa_force_ditolak(): void
    {
        $rt = $this->buatRtImpor();
        $fixture = $this->buatCsvFixture();

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path'],
            '--tenant' => $rt->id,
            '--execute' => true,
        ])
            ->assertFailed()
            ->expectsOutputToContain('--force');

        File::delete($fixture['path']);
    }

    public function test_impor_csv_kecil_dan_idempoten(): void
    {
        $rt = $this->buatRtImpor();
        $fixture = $this->buatCsvFixture();

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path'],
            '--tenant' => $rt->id,
            '--execute' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame($fixture['keluarga'], Keluarga::query()->where('tenant_id', $rt->id)->count());
        $this->assertSame($fixture['warga'], Warga::query()->where('tenant_id', $rt->id)->count());

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path'],
            '--tenant' => $rt->id,
            '--execute' => true,
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Keluarga dilewati: '.$fixture['keluarga']);

        $this->assertSame($fixture['keluarga'], Keluarga::query()->where('tenant_id', $rt->id)->count());
        $this->assertSame($fixture['warga'], Warga::query()->where('tenant_id', $rt->id)->count());

        File::delete($fixture['path']);
    }

    public function test_pemetaan_status_rumah_dan_warna_hijau_serta_nik(): void
    {
        $rt = $this->buatRtImpor();
        $fixture = $this->buatCsvFixture();

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path'],
            '--tenant' => $rt->id,
            '--execute' => true,
            '--force' => true,
        ])->assertSuccessful();

        $milik = Keluarga::query()
            ->where('tenant_id', $rt->id)
            ->whereHas('warga', fn ($q) => $q->where('nama', 'ARI MULYA'))
            ->firstOrFail();
        $this->assertSame('milik', $milik->status_hunian);

        $sewa = Keluarga::query()
            ->where('tenant_id', $rt->id)
            ->whereHas('warga', fn ($q) => $q->where('nama', 'HIJAU KELUARGA'))
            ->firstOrFail();
        $this->assertSame('sewa', $sewa->status_hunian);
        $this->assertSame('nonaktif', $sewa->status);

        $wargaNik = Warga::query()
            ->where('tenant_id', $rt->id)
            ->where('nama', 'NIK INVALID')
            ->firstOrFail();
        $this->assertNull($wargaNik->nik);
        $this->assertStringContainsString('NIK di file tidak valid atau kosong', (string) $wargaNik->catatan);

        File::delete($fixture['path']);
    }
}
