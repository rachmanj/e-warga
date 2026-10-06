<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_admin_dengan_sandi_dari_config(): void
    {
        config(['app.seed_admin_password' => 'sandi-produksi-rahasia']);

        $this->seed(RolePermissionSeeder::class);

        $admin = User::query()->withoutGlobalScope('tenant')->where('username', 'admin')->first();

        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('sandi-produksi-rahasia', $admin->password));
    }

    public function test_seeder_tidak_mengubah_sandi_admin_yang_sudah_ada(): void
    {
        $admin = User::query()->withoutGlobalScope('tenant')->create([
            'username' => 'admin',
            'nama' => 'Administrator',
            'name' => 'Administrator',
            'email' => 'admin@ewarga.local',
            'tenant_id' => null,
            'aktif' => true,
            'password' => Hash::make('sandi-lama-tetap'),
        ]);

        config(['app.seed_admin_password' => 'sandi-baru-dari-config']);

        $this->seed(RolePermissionSeeder::class);

        $admin->refresh();

        $this->assertTrue(Hash::check('sandi-lama-tetap', $admin->password));
        $this->assertFalse(Hash::check('sandi-baru-dari-config', $admin->password));
    }
}
