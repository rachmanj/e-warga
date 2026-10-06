<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FondasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_sukses_dengan_username(): void
    {
        $response = $this->post('/login', [
            'username' => 'ketua',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::query()->withoutGlobalScope('tenant')->where('username', 'ketua')->first());
    }

    public function test_login_dengan_email_ditolak(): void
    {
        $response = $this->from('/login')->post('/login', [
            'username' => 'ketua@rt05.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_pengguna_tanpa_kelola_pengguna_mendapat_403(): void
    {
        $user = User::query()->withoutGlobalScope('tenant')->where('username', 'pengurus')->firstOrFail();

        $response = $this->actingAs($user)->get('/pengguna');

        $response->assertForbidden();
    }

    public function test_ubah_sandi_berhasil(): void
    {
        $user = User::query()->withoutGlobalScope('tenant')->where('username', 'ketua')->firstOrFail();

        $response = $this->actingAs($user)
            ->post('/ubah-sandi', [
                'current_password' => 'password',
                'password' => 'barubanget',
                'password_confirmation' => 'barubanget',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('barubanget', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_ubah_sandi_gagal_jika_sandi_saat_ini_salah(): void
    {
        $user = User::query()->withoutGlobalScope('tenant')->where('username', 'sekretaris')->firstOrFail();

        $response = $this->actingAs($user)
            ->from('/ubah-sandi')
            ->post('/ubah-sandi', [
                'current_password' => 'salah',
                'password' => 'barubanget',
                'password_confirmation' => 'barubanget',
            ]);

        $response->assertRedirect('/ubah-sandi');
        $response->assertSessionHasErrors('current_password');
    }
}
