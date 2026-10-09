<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\SuratJenis;
use App\Models\Warga;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuratJenisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WargaProfilSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_kolom_profil_warga_dapat_diisi_dan_disimpan(): void
    {
        $rt = $this->buatRt();
        $keluarga = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => '3201234567890999',
            'alamat' => 'Jl. Uji',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);

        $warga = Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga->id,
            'nama' => 'Siti Aminah',
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
            'tempat_lahir' => 'Balikpapan',
            'pendidikan' => 'S1',
            'kewarganegaraan' => 'WNI',
            'golongan_darah' => 'O',
        ]);

        $warga->refresh();

        $this->assertSame('Balikpapan', $warga->tempat_lahir);
        $this->assertSame('S1', $warga->pendidikan);
        $this->assertSame('WNI', $warga->kewarganegaraan);
        $this->assertSame('O', $warga->golongan_darah);
    }

    public function test_perbarui_profil_mengisi_warga_tanpa_menambah_baris_dan_idempoten(): void
    {
        $rt = $this->buatRt();
        $fixture = $this->buatCsvProfilFixture();

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path_impor'],
            '--tenant' => $rt->id,
            '--execute' => true,
            '--force' => true,
        ])->assertSuccessful();

        $keluargaSebelum = Keluarga::query()->count();
        $wargaSebelum = Warga::query()->count();

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path_profil'],
            '--tenant' => $rt->id,
            '--perbarui-profil' => true,
            '--execute' => true,
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Warga diperbarui: 1');

        $this->assertSame($keluargaSebelum, Keluarga::query()->count());
        $this->assertSame($wargaSebelum, Warga::query()->count());

        $warga = Warga::query()->where('nama', 'BUDI PROFIL')->firstOrFail();
        $this->assertSame('Samarinda', $warga->tempat_lahir);
        $this->assertSame('SMA', $warga->pendidikan);
        $this->assertSame('A', $warga->golongan_darah);
        $this->assertSame('WNI', $warga->kewarganegaraan);

        $this->artisan('warga:import-rt', [
            '--file' => $fixture['path_profil'],
            '--tenant' => $rt->id,
            '--perbarui-profil' => true,
            '--execute' => true,
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Warga diperbarui: 0')
            ->expectsOutputToContain('Warga dilewati: 1');

        File::delete($fixture['path_impor']);
        File::delete($fixture['path_profil']);
    }

    public function test_seeder_surat_pengantar_sp_idempoten_dengan_format_nomor_benar(): void
    {
        $rt = $this->buatRt();

        $this->seed(SuratJenisSeeder::class);

        $sp = SuratJenis::query()->withoutGlobalScope('tenant')
            ->where('tenant_id', $rt->id)
            ->where('kode', 'SP')
            ->first();

        $this->assertNotNull($sp);
        $this->assertSame('Surat Pengantar', $sp->nama);
        $this->assertSame('{urut}/GSI-32/{tahun}', $sp->format_nomor);
        $this->assertTrue($sp->butuh_data_warga);
        $this->assertTrue($sp->aktif);
        $this->assertSame(0, $sp->urutan);
        $this->assertStringContainsString('{{daftar_keperluan}}', $sp->template_body);
        $this->assertStringContainsString('{{nik}}', $sp->template_body);

        $jumlahSp = SuratJenis::query()->withoutGlobalScope('tenant')
            ->where('tenant_id', $rt->id)
            ->where('kode', 'SP')
            ->count();
        $this->assertSame(1, $jumlahSp);

        $skd = SuratJenis::query()->withoutGlobalScope('tenant')
            ->where('tenant_id', $rt->id)
            ->where('kode', 'SKD')
            ->firstOrFail();
        $this->assertSame('{urut}/{kode}/{rt}/{rw}/{tahun}', $skd->format_nomor);
    }

    private function buatRt(): Rt
    {
        return Rt::query()->create([
            'nama' => 'RT 99',
            'slug' => 'rt-profil-'.uniqid('', true),
            'rw' => '01',
            'kelurahan' => 'Test',
            'kecamatan' => 'Test',
            'kota' => 'Test',
        ]);
    }

    /**
     * @return array{path_impor: string, path_profil: string}
     */
    private function buatCsvProfilFixture(): array
    {
        $header = 'baris,no_urut,no_kk,no_kk_16,rt,no_rumah,nama_kepala,no_hp,nama_anggota,nik_16,nik_asli,jk,tanggal_lahir,jumlah_anggota,tempat_lahir,gol_darah,grade,pendidikan,pekerjaan,agama,status_rumah,bantuan,warna,is_kepala,keluarga_index,keluarga_no_kk,keluarga_nama_kepala,keluarga_warna,keluarga_status_rumah';

        $pathImpor = storage_path('framework/testing/rt-profil-impor-'.uniqid('', true).'.csv');
        $pathProfil = storage_path('framework/testing/rt-profil-update-'.uniqid('', true).'.csv');

        $impor = '1,1,3201234567890123,3201234567890123,99,1,BUDI PROFIL,,BUDI PROFIL,3201234567890124,,L,1990-01-01,1,,,,,,ISLAM,R/S,,,True,1,3201234567890123,BUDI PROFIL,,RUMAH SENDIRI';
        $profil = '1,1,3201234567890123,3201234567890123,99,1,budi profil,,budi profil,3201234567890124,,L,1990-01-01,1,Samarinda,A,,SMA,,ISLAM,R/S,,,True,1,3201234567890123,budi profil,,RUMAH SENDIRI';

        File::put($pathImpor, $header."\n".$impor."\n");
        File::put($pathProfil, $header."\n".$profil."\n");

        return [
            'path_impor' => $pathImpor,
            'path_profil' => $pathProfil,
        ];
    }
}
