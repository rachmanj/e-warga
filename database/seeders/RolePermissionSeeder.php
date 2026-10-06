<?php

namespace Database\Seeders;

use App\Models\Rt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $permissions = [
        'kelola_rt',
        'lihat_warga',
        'kelola_warga',
        'lihat_dokumen',
        'kelola_dokumen',
        'lihat_kas',
        'kelola_kas',
        'lihat_surat',
        'kelola_surat',
        'terbitkan_surat',
        'kelola_pengguna',
        'ubah_sandi',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superadmin = Role::findOrCreate('superadmin');
        $superadmin->syncPermissions(Permission::all());

        $ketua = Role::findOrCreate('ketua_rt');
        $ketua->syncPermissions([
            'kelola_rt',
            'lihat_warga',
            'lihat_dokumen',
            'lihat_kas',
            'kelola_kas',
            'lihat_surat',
            'kelola_surat',
            'terbitkan_surat',
            'ubah_sandi',
        ]);

        $sekretaris = Role::findOrCreate('sekretaris');
        $sekretaris->syncPermissions([
            'lihat_warga',
            'kelola_warga',
            'lihat_dokumen',
            'kelola_dokumen',
            'lihat_surat',
            'kelola_surat',
            'ubah_sandi',
        ]);

        $bendahara = Role::findOrCreate('bendahara');
        $bendahara->syncPermissions([
            'lihat_warga',
            'lihat_kas',
            'kelola_kas',
            'lihat_surat',
            'ubah_sandi',
        ]);

        $pengurus = Role::findOrCreate('pengurus');
        $pengurus->syncPermissions([
            'lihat_warga',
            'lihat_kas',
            'lihat_surat',
            'ubah_sandi',
        ]);

        $rt = Rt::query()->firstOrCreate(
            ['slug' => 'rt-05'],
            [
                'nama' => 'RT 05',
                'rw' => '03',
                'kelurahan' => 'Gunung Sari Ilir',
                'kecamatan' => 'Balikpapan Tengah',
                'kota' => 'Balikpapan',
                'kode_pos' => null,
                'nama_ketua' => 'Ketua RT 05',
                'nama_sekretaris' => 'Sekretaris RT 05',
                'nama_bendahara' => 'Bendahara RT 05',
                'publik_aktif' => true,
            ]
        );

        $this->seedUser([
            'username' => 'admin',
            'nama' => 'Administrator',
            'email' => 'admin@ewarga.local',
            'tenant_id' => null,
            'role' => 'superadmin',
        ]);

        $this->seedUser([
            'username' => 'ketua',
            'nama' => 'Ketua RT 05',
            'email' => 'ketua@rt05.local',
            'tenant_id' => $rt->id,
            'role' => 'ketua_rt',
        ]);

        $this->seedUser([
            'username' => 'sekretaris',
            'nama' => 'Sekretaris RT 05',
            'email' => 'sekretaris@rt05.local',
            'tenant_id' => $rt->id,
            'role' => 'sekretaris',
        ]);

        $this->seedUser([
            'username' => 'bendahara',
            'nama' => 'Bendahara RT 05',
            'email' => 'bendahara@rt05.local',
            'tenant_id' => $rt->id,
            'role' => 'bendahara',
        ]);

        $this->seedUser([
            'username' => 'pengurus',
            'nama' => 'Pengurus RT 05',
            'email' => 'pengurus@rt05.local',
            'tenant_id' => $rt->id,
            'role' => 'pengurus',
        ]);
    }

    /**
     * @param  array{username: string, nama: string, email: string, tenant_id: int|null, role: string}  $data
     */
    private function seedUser(array $data): void
    {
        $user = User::query()->withoutGlobalScope('tenant')->firstOrNew([
            'username' => $data['username'],
        ]);

        $user->fill([
            'nama' => $data['nama'],
            'name' => $data['nama'],
            'email' => $data['email'],
            'tenant_id' => $data['tenant_id'],
            'aktif' => true,
        ]);

        if (! $user->exists) {
            $user->password = Hash::make('password');
        }

        $user->save();

        if (! $user->hasRole($data['role'])) {
            $user->syncRoles([$data['role']]);
        }
    }
}
