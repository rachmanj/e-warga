<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_manifest_webmanifest_dapat_diakses_dan_valid(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('e-Warga - Pengelolaan RT', $data['name']);
        $this->assertSame('e-Warga', $data['short_name']);
        $this->assertCount(3, $data['icons']);
    }

    public function test_service_worker_dapat_diakses_sebagai_javascript(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();
        $this->assertStringContainsString('javascript', (string) $response->headers->get('Content-Type'));
    }

    public function test_halaman_offline_dapat_diakses(): void
    {
        $response = $this->get('/offline.html');

        $response->assertOk();
    }

    public function test_halaman_login_memuat_manifest_dan_pwa_js(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('/js/pwa.js', false);
    }

    public function test_precache_service_worker_tidak_memuat_rute_sensitif(): void
    {
        $content = file_get_contents(public_path('sw.js'));
        $this->assertIsString($content);

        if (! preg_match('/const PRECACHE_URLS = \[(.*?)\];/s', $content, $matches)) {
            $this->fail('Daftar PRECACHE_URLS tidak ditemukan di sw.js');
        }

        $precacheList = $matches[1];
        $forbidden = ['login', 'warga', 'dokumen', 'surat'];

        foreach ($forbidden as $word) {
            $this->assertStringNotContainsString(
                $word,
                $precacheList,
                "Precache tidak boleh memuat kata «{$word}»."
            );
        }
    }
}
