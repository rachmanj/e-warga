<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Support\ActiveRt;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PengaturanRtTest extends TestCase
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

    /**
     * @return array<string, string>
     */
    private function payloadRtLengkap(): array
    {
        $rt = $this->rtUtama();

        return [
            'nama' => $rt->nama,
            'nama_ketua' => $rt->nama_ketua ?? '',
            'nama_sekretaris' => $rt->nama_sekretaris ?? '',
            'nama_bendahara' => $rt->nama_bendahara ?? '',
            'alamat_sekretariat' => $rt->alamat_sekretariat ?? '',
            'kode_pos' => $rt->kode_pos ?? '',
            'no_hp' => $rt->no_hp ?? '',
            'rw' => $rt->rw ?? '',
            'kelurahan' => $rt->kelurahan ?? '',
            'kecamatan' => $rt->kecamatan ?? '',
            'kota' => $rt->kota ?? '',
        ];
    }

    public function test_ketua_rt_dapat_menyimpan_nama_sekretaris_dan_bendahara(): void
    {
        $this->loginSebagai('ketua');

        $this->get(route('pengaturan.index'))
            ->assertOk()
            ->assertSee('Data RT')
            ->assertSee('name="nama_sekretaris"', false);

        $payload = $this->payloadRtLengkap();
        $payload['nama_sekretaris'] = 'Sekretaris Baru';
        $payload['nama_bendahara'] = 'Bendahara Baru';

        $this->post(route('pengaturan.rt.update'), $payload)
            ->assertRedirect(route('pengaturan.index'))
            ->assertSessionHas('status');

        $rt = $this->rtUtama()->fresh();
        $this->assertSame('Sekretaris Baru', $rt->nama_sekretaris);
        $this->assertSame('Bendahara Baru', $rt->nama_bendahara);
    }

    public function test_pengurus_tidak_melihat_formulir_dan_post_ditolak_403(): void
    {
        $this->loginSebagai('pengurus');

        $this->get(route('pengaturan.index'))
            ->assertOk()
            ->assertSee('Akun')
            ->assertDontSee('name="nama_sekretaris"', false)
            ->assertDontSee(route('pengaturan.rt.update'), false);

        $this->post(route('pengaturan.rt.update'), $this->payloadRtLengkap())
            ->assertForbidden();
    }

    public function test_validasi_menolak_kode_pos_tidak_valid_dan_nama_rt_kosong(): void
    {
        $this->loginSebagai('ketua');

        $payload = $this->payloadRtLengkap();
        $payload['kode_pos'] = '1234';

        $this->from(route('pengaturan.index'))
            ->post(route('pengaturan.rt.update'), $payload)
            ->assertRedirect(route('pengaturan.index'))
            ->assertSessionHasErrors('kode_pos');

        $payload = $this->payloadRtLengkap();
        $payload['nama'] = '';

        $this->from(route('pengaturan.index'))
            ->post(route('pengaturan.rt.update'), $payload)
            ->assertRedirect(route('pengaturan.index'))
            ->assertSessionHasErrors('nama');
    }

    public function test_perubahan_mencatat_satu_baris_activity_log(): void
    {
        $this->loginSebagai('ketua');

        $this->assertDatabaseCount('activity_log', 0);

        $payload = $this->payloadRtLengkap();
        $payload['nama_ketua'] = 'Ketua Diperbarui';

        $this->post(route('pengaturan.rt.update'), $payload)->assertRedirect();

        $this->assertDatabaseCount('activity_log', 1);

        $log = Activity::query()->first();
        $this->assertNotNull($log);
        $this->assertSame('rt_identitas_diubah', $log->event);

        $properties = $log->properties->toArray();
        $this->assertArrayHasKey('nama_pengguna', $properties);
        $this->assertArrayHasKey('kolom_diubah', $properties);
        $this->assertContains('nama_ketua', $properties['kolom_diubah']);
        $this->assertSame('Ketua RT 05', $properties['nama_pengguna']);
    }
}
