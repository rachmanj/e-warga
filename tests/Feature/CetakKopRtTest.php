<?php

namespace Tests\Feature;

use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\Kwitansi;
use App\Models\Rt;
use App\Models\Surat;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class CetakKopRtTest extends TestCase
{
    use RefreshDatabase;

    public function test_kop_surat_pdf_menghilangkan_rw_minus(): void
    {
        $rt = $this->buatRt(['rw' => '-']);
        $html = $this->renderSuratPdf($rt);

        $this->assertKopWilayahValid($html);
        $this->assertStringContainsString('Kel. Gunung Sari Ilir', $html);
        $this->assertStringContainsString('Kec. Balikpapan Tengah', $html);
    }

    public function test_kop_kwitansi_pdf_menghilangkan_rw_minus(): void
    {
        $rt = $this->buatRt(['rw' => '--']);
        $html = $this->renderKwitansiPdf($rt);

        $this->assertKopWilayahValid($html);
        $this->assertStringContainsString('Kel. Gunung Sari Ilir', $html);
    }

    public function test_kop_surat_pdf_menampilkan_rw_terisi(): void
    {
        $rt = $this->buatRt(['rw' => '05']);
        $html = $this->renderSuratPdf($rt);

        $this->assertStringContainsString('RW 05', $html);
        $this->assertKopWilayahValid($html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatRt(array $overrides = []): Rt
    {
        return Rt::query()->create(array_merge([
            'nama' => 'RT 01',
            'rw' => '-',
            'kelurahan' => 'Gunung Sari Ilir',
            'kecamatan' => 'Balikpapan Tengah',
            'kota' => 'Balikpapan',
            'slug' => 'rt-kop-test',
            'publik_aktif' => true,
        ], $overrides));
    }

    private function renderSuratPdf(Rt $rt): string
    {
        $surat = new Surat([
            'nomor_lengkap' => '001/SKD/2025',
        ]);
        $surat->tanggal_terbit = Carbon::parse('2025-06-01');

        return View::make('surat.pdf', [
            'surat' => $surat,
            'rt' => $rt,
            'isi' => '<p>Isi uji kop.</p>',
            'verifikasiUrl' => 'https://example.test/verifikasi/abc',
        ])->render();
    }

    private function renderKwitansiPdf(Rt $rt): string
    {
        $jenis = new IuranJenis(['nama' => 'Iuran Kebersihan']);
        $tagihan = new IuranTagihan([
            'periode' => '2025-06',
            'nominal' => '25000.00',
        ]);
        $tagihan->setRelation('jenis', $jenis);
        $tagihan->setRelation('keluarga', null);

        $pembayaran = new IuranPembayaran([
            'jumlah' => '25000.00',
            'metode' => 'tunai',
            'tanggal' => Carbon::parse('2025-06-15'),
        ]);
        $pembayaran->setRelation('tagihan', $tagihan);

        $kwitansi = new Kwitansi(['nomor' => 'KW-001/2025']);

        return View::make('iuran.kwitansi-pdf', [
            'rt' => $rt,
            'kwitansi' => $kwitansi,
            'pembayaran' => $pembayaran,
            'tagihan' => $tagihan,
            'kepala' => null,
        ])->render();
    }

    private function assertKopWilayahValid(string $html): void
    {
        $this->assertStringNotContainsString('RW -', $html);
        $this->assertStringNotContainsString(' · ·', $html);
        $this->assertDoesNotMatchRegularExpression('/<p>\s*·/', $html);
        $this->assertDoesNotMatchRegularExpression('/·\s*<\/p>/', $html);
    }
}
