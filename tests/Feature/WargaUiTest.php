<?php

namespace Tests\Feature;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Warga;
use App\Support\ActiveRt;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WargaUiTest extends TestCase
{
    use RefreshDatabase;

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

    private function buatKeluarga(int $indeks): Keluarga
    {
        $rt = $this->rtUtama();
        $noKk = sprintf('320123456789%04d', $indeks);
        $nik = sprintf('327601234567%04d', $indeks);

        $keluarga = Keluarga::query()->create([
            'tenant_id' => $rt->id,
            'no_kk' => $noKk,
            'alamat' => "Jl. Contoh No. {$indeks}",
            'blok_unit' => (string) $indeks,
            'status_hunian' => 'milik',
            'status' => 'aktif',
        ]);

        Warga::query()->create([
            'tenant_id' => $rt->id,
            'keluarga_id' => $keluarga->id,
            'nik' => $nik,
            'nama' => "Kepala {$indeks}",
            'hubungan' => 'kepala',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        return $keluarga;
    }

    public function test_halaman_data_warga_pagination_bootstrap_dan_nomor_urut(): void
    {
        $this->loginSebagai('sekretaris');

        for ($i = 1; $i <= 16; $i++) {
            $this->buatKeluarga($i);
        }

        $halamanPertama = $this->get(route('warga.index'));
        $halamanPertama->assertOk();
        $halamanPertama->assertSee('>No.<', false);
        $halamanPertama->assertSee('pagination', false);
        $halamanPertama->assertSee('page-item active', false);
        $halamanPertama->assertSee('>1<', false);
        $halamanPertama->assertSee('>2<', false);
        $halamanPertama->assertSee('Sebelumnya', false);
        $halamanPertama->assertSee('Berikutnya', false);
        $halamanPertama->assertDontSee('Previous', false);
        $halamanPertama->assertDontSee('Next', false);

        $halamanKedua = $this->get(route('warga.index', ['page' => 2]));
        $halamanKedua->assertOk();
        $halamanKedua->assertSee('>16<', false);
        $halamanKedua->assertSee('<td>16</td>', false);
    }

    public function test_navbar_memuat_pengalih_mode_warna(): void
    {
        $this->loginSebagai('ketua');

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('id="ewarga-color-mode-toggle"', false);
        $response->assertSee('Aktifkan mode gelap', false);
    }

    public function test_halaman_ubah_kata_sandi_dan_validasi_sandi_lama(): void
    {
        $this->loginSebagai('ketua');

        $this->get(route('ubah-sandi.edit'))
            ->assertOk()
            ->assertSee('Kata sandi lama', false)
            ->assertSee('Konfirmasi kata sandi baru', false);

        $this->from(route('ubah-sandi.edit'))
            ->post(route('ubah-sandi.update'), [
                'current_password' => 'salah',
                'password' => 'barubanget',
                'password_confirmation' => 'barubanget',
            ])
            ->assertRedirect(route('ubah-sandi.edit'))
            ->assertSessionHasErrors('current_password');
    }
}
