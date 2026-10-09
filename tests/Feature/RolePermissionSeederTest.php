<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
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

    public function test_ketua_rt_memiliki_izin_kelola_warga_dan_kelola_dokumen_setelah_seeder(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $ketua = Role::findByName('ketua_rt');

        $this->assertTrue($ketua->hasPermissionTo('kelola_warga'));
        $this->assertTrue($ketua->hasPermissionTo('kelola_dokumen'));
    }

    public function test_pengurus_tidak_memiliki_izin_kelola_warga_kelola_dokumen_dan_kas_setelah_seeder(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $pengurus = Role::findByName('pengurus');

        $this->assertFalse($pengurus->hasPermissionTo('kelola_warga'));
        $this->assertFalse($pengurus->hasPermissionTo('kelola_dokumen'));
        $this->assertFalse($pengurus->hasPermissionTo('lihat_kas'));
        $this->assertFalse($pengurus->hasPermissionTo('kelola_kas'));
    }
}
