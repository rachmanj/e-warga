<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\User;
use App\Support\ActiveRt;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenancyScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_setelah_login_admin_mengembalikan_200(): void
    {
        $rt = Rt::query()->where('slug', 'rt-05')->firstOrFail();

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        session([ActiveRt::SESSION_KEY => $rt->id]);

        $response = $this->get('/dashboard');

        $response->assertOk();
    }

    public function test_tenant_scope_id_mengembalikan_null_saat_guard_belum_memiliki_pengguna(): void
    {
        $this->assertGuest();
        $this->assertFalse(Auth::hasUser());

        $this->assertNull(ActiveRt::tenantScopeId());
    }

    public function test_memuat_user_tanpa_pengguna_hanya_satu_query_ke_tabel_users(): void
    {
        $this->assertGuest();

        DB::enableQueryLog();

        User::query()->where('username', 'admin')->first();

        $usersQueries = array_filter(
            DB::getQueryLog(),
            fn (array $entry): bool => preg_match('/\busers\b/i', $entry['query']) === 1
        );

        $this->assertCount(1, $usersQueries);
    }
}
