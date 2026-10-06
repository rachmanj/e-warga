<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsBehindReverseProxyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_redirect_location_memakai_https_di_balik_proxy(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://ewarga.test',
        ]);

        if (config('app.env') === 'production' && str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        $user = User::query()->withoutGlobalScope('tenant')->where('username', 'ketua')->firstOrFail();

        $response = $this->actingAs($user)
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('/login');

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertTrue(
            str_starts_with($location, 'https://'),
            'Location redirect harus memakai skema https, diterima: '.$location
        );
        $this->assertFalse(
            str_starts_with($location, 'http://'),
            'Location redirect tidak boleh memakai skema http, diterima: '.$location
        );
    }
}
