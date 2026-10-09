<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Warga;
use App\Support\ActiveRt;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WargaModuleTest extends TestCase
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

    public function test_menambah_keluarga_beserta_anggota_kepala(): void
    {
        $this->loginSebagai('sekretaris');

        $response = $this->post(route('warga.store'), [
            'no_kk' => '3201234567890123',
            'alamat' => 'Jl. Kenanga 5',
            'status_hunian' => 'sewa',
            'status' => 'aktif',
            'nama' => 'Siti Aminah',
            'nik' => '3276012345678999',
            'jenis_kelamin' => 'P',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('keluarga', ['alamat' => 'Jl. Kenanga 5']);
        $this->assertDatabaseHas('warga', ['nama' => 'Siti Aminah', 'hubungan' => 'kepala']);
    }

    public function test_nomor_kk_duplikat_dalam_satu_rt_ditolak(): void
    {
        $this->loginSebagai('sekretaris');
        $this->buatKeluargaDenganKepala();

        $response = $this->from(route('warga.create'))->post(route('warga.store'), [
            'no_kk' => self::KK,
            'alamat' => 'Alamat lain',
            'status_hunian' => 'milik',
            'status' => 'aktif',
            'nama' => 'Orang Lain',
            'jenis_kelamin' => 'L',
        ]);

        $response->assertRedirect(route('warga.create'));
        $response->assertSessionHasErrors('no_kk');
    }

    public function test_menambah_keluarga_tanpa_nomor_kk_ditolak(): void
    {
        $this->loginSebagai('sekretaris');

        $response = $this->from(route('warga.create'))->post(route('warga.store'), [
            'alamat' => 'Jl. Tanpa KK',
            'status_hunian' => 'milik',
            'status' => 'aktif',
            'nama' => 'Tanpa KK',
            'jenis_kelamin' => 'L',
        ]);

        $response->assertRedirect(route('warga.create'));
        $response->assertSessionHasErrors('no_kk');
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadUbahKeluarga(array $overrides = []): array
    {
        return array_merge([
            'alamat' => 'Jl. Merdeka No. 1',
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ], $overrides);
    }

    public function test_mengubah_alamat_keluarga_tanpa_nomor_kk_mempertahankan_kk(): void
    {
        $this->loginSebagai('sekretaris');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $sebelum = DB::table('keluarga')->where('id', $keluarga->id)->first(['no_kk', 'no_kk_hash']);
        $this->assertNotNull($sebelum);

        $response = $this->put(route('warga.update', $keluarga), $this->payloadUbahKeluarga([
            'alamat' => 'Jl. Merdeka No. 99',
            'no_kk' => '',
        ]));

        $response->assertRedirect(route('warga.show', $keluarga));
        $this->assertDatabaseHas('keluarga', ['id' => $keluarga->id, 'alamat' => 'Jl. Merdeka No. 99']);

        $sesudah = DB::table('keluarga')->where('id', $keluarga->id)->first(['no_kk', 'no_kk_hash']);
        $this->assertSame($sebelum->no_kk, $sesudah->no_kk);
        $this->assertSame($sebelum->no_kk_hash, $sesudah->no_kk_hash);
    }

    public function test_mengubah_nomor_kk_valid_memperbarui_nilai_dan_hash(): void
    {
        $this->loginSebagai('sekretaris');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $kkBaru = '3201234567890999';
        $hashBaru = hash_hmac('sha256', $kkBaru, config('app.key'));

        $sebelum = DB::table('keluarga')->where('id', $keluarga->id)->first(['no_kk', 'no_kk_hash']);
        $this->assertNotSame($hashBaru, $sebelum->no_kk_hash);

        $response = $this->put(route('warga.update', $keluarga), $this->payloadUbahKeluarga([
            'no_kk' => $kkBaru,
        ]));

        $response->assertRedirect(route('warga.show', $keluarga));

        $sesudah = DB::table('keluarga')->where('id', $keluarga->id)->first(['no_kk', 'no_kk_hash']);
        $this->assertNotSame($sebelum->no_kk, $sesudah->no_kk);
        $this->assertSame($hashBaru, $sesudah->no_kk_hash);
        $this->assertSame($kkBaru, $keluarga->fresh()->no_kk);
    }

    public function test_nomor_kk_duplikat_saat_ubah_keluarga_ditolak(): void
    {
        $this->loginSebagai('sekretaris');
        $this->buatKeluargaDenganKepala();

        $keluargaLain = Keluarga::query()->create([
            'tenant_id' => $this->rtUtama()->id,
            'no_kk' => '3201234567890123',
            'alamat' => 'Jl. Lain',
            'status_hunian' => 'sewa',
            'status' => 'aktif',
        ]);

        $response = $this->from(route('warga.edit', $keluargaLain))->put(route('warga.update', $keluargaLain), $this->payloadUbahKeluarga([
            'alamat' => 'Jl. Lain Diubah',
            'no_kk' => self::KK,
        ]));

        $response->assertRedirect(route('warga.edit', $keluargaLain));
        $response->assertSessionHasErrors('no_kk');
        $this->assertDatabaseHas('keluarga', ['id' => $keluargaLain->id, 'alamat' => 'Jl. Lain']);
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

        $this->loginSebagai('sekretaris');

        $this->get(route('warga.show', $keluargaLain))->assertNotFound();
        $this->get(route('warga.edit', $keluargaLain))->assertNotFound();
    }

    public function test_halaman_daftar_dan_detail_tidak_memuat_kk_atau_nik_penuh(): void
    {
        $this->loginSebagai('sekretaris');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $index = $this->get(route('warga.index'));
        $index->assertOk();
        $index->assertDontSee(self::KK, false);
        $index->assertDontSee(self::NIK, false);

        $detail = $this->get(route('warga.show', $keluarga));
        $detail->assertOk();
        $detail->assertDontSee(self::KK, false);
        $detail->assertDontSee(self::NIK, false);
        $detail->assertSee('3276........8901', false);
    }

    public function test_pengurus_tidak_bisa_menambah_keluarga_tetapi_bisa_lihat_daftar(): void
    {
        $this->loginSebagai('pengurus');

        $this->get(route('warga.create'))->assertForbidden();
        $this->post(route('warga.store'), [
            'no_kk' => '3201234567890456',
            'alamat' => 'Test',
            'status_hunian' => 'milik',
            'status' => 'aktif',
            'nama' => 'Test',
            'jenis_kelamin' => 'L',
        ])->assertForbidden();

        $this->get(route('warga.index'))->assertOk();
    }

    public function test_unggah_dokumen_berhasil_dan_tidak_di_public(): void
    {
        Storage::fake('local');
        $this->loginSebagai('sekretaris');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $file = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');

        $response = $this->post(route('warga.dokumen.store', $keluarga), [
            'jenis' => 'ktp',
            'berkas' => $file,
        ]);

        $response->assertRedirect(route('warga.show', $keluarga));

        $dokumen = $keluarga->fresh()->dokumen->first();
        $this->assertNotNull($dokumen);
        Storage::disk('local')->assertExists($dokumen->file_path);
        $this->assertStringStartsWith('dokumen-warga/', $dokumen->file_path);
        $this->assertFileDoesNotExist(public_path('storage/'.$dokumen->file_path));
    }

    public function test_menghapus_anggota_keluarga(): void
    {
        $this->loginSebagai('sekretaris');
        ['keluarga' => $keluarga] = $this->buatKeluargaDenganKepala();

        $anak = Warga::query()->create([
            'tenant_id' => $keluarga->tenant_id,
            'keluarga_id' => $keluarga->id,
            'nama' => 'Anak Satu',
            'hubungan' => 'anak',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $this->delete(route('warga.anggota.destroy', $anak))
            ->assertRedirect(route('warga.show', $keluarga));

        $this->assertDatabaseMissing('warga', ['id' => $anak->id]);
    }

    public function test_mutasi_tercatat(): void
    {
        $this->loginSebagai('sekretaris');
        ['keluarga' => $keluarga, 'warga' => $warga] = $this->buatKeluargaDenganKepala();

        $this->post(route('warga.mutasi.store', $keluarga), [
            'jenis' => 'keluar',
            'tanggal' => '2026-01-15',
            'warga_id' => $warga->id,
            'keterangan' => 'Pindah ke luar kota',
        ])->assertRedirect(route('warga.show', $keluarga));

        $this->assertDatabaseHas('warga_mutasi', [
            'keluarga_id' => $keluarga->id,
            'warga_id' => $warga->id,
            'jenis' => 'keluar',
        ]);
    }
}
