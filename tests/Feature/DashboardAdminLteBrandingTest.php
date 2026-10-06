<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdminLteBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_pengguna_rt_tidak_memuat_string_adminlte(): void
    {
        $this->post('/login', [
            'username' => 'ketua',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $response = $this->get('/dashboard');

        $response->assertOk();
        $this->assertStringNotContainsString('AdminLTE', $response->getContent());
    }
}
