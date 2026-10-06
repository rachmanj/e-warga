<?php

namespace Tests\Feature;

use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\Kwitansi;
use App\Models\Rt;
use App\Models\Surat;
use App\Support\KopRt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class CetakKopRtTest extends TestCase
{
    use RefreshDatabase;

    public function test_kop_menampilkan_lima_baris_saat_data_rt_lengkap(): void
    {
        $rt = $this->buatRtLengkap();
        $baris = KopRt::barisKop($rt);

        $this->assertCount(5, $baris);
        $this->assertSame('BALIKPAPAN TENGAH', $baris[0]);
        $this->assertSame('KELURAHAN GUNUNG SARI ILIR', $baris[1]);
        $this->assertSame('RUKUN TETANGGA 32', $baris[2]);
        $this->assertSame('Jl. Sekretariat RT 32 76122', $baris[3]);
        $this->assertSame('No. Hp. 081234567890', $baris[4]);

        $htmlSurat = $this->renderSuratPdf($rt);
        $this->assertStringContainsString($baris[0], $htmlSurat);
        $this->assertStringContainsString($baris[4], $htmlSurat);
        $this->assertStringContainsString('<h2>RT 32</h2>', $htmlSurat);

        $htmlKwitansi = $this->renderKwitansiPdf($rt);
        $this->assertStringContainsString('RUKUN TETANGGA 32', $htmlKwitansi);
    }

    public function test_kop_melewati_baris_kosong_dan_placeholder_minus(): void
    {
        $rt = Rt::query()->create([
            'nama' => 'RT Tanpa Angka',
            'slug' => 'rt-kop-minimal',
            'rw' => '-',
            'kelurahan' => '',
            'kecamatan' => 'Satu Kecamatan',
            'kota' => 'Balikpapan',
            'kode_pos' => '--',
            'alamat_sekretariat' => null,
            'no_hp' => '',
            'publik_aktif' => true,
        ]);

        $baris = KopRt::barisKop($rt);

        $this->assertSame([
            'SATU KECAMATAN',
            'RUKUN TETANGGA RT Tanpa Angka',
        ], $baris);

        $html = $this->renderSuratPdf($rt);
        $this->assertStringNotContainsString('KELURAHAN', $html);
        $this->assertStringNotContainsString('No. Hp.', $html);
        $this->assertStringNotContainsString('RW -', $html);
    }

    private function buatRtLengkap(): Rt
    {
        return Rt::query()->create([
            'nama' => 'RT 32',
            'slug' => 'rt-kop-lengkap',
            'rw' => '05',
            'kelurahan' => 'Gunung Sari Ilir',
            'kecamatan' => 'Balikpapan Tengah',
            'kota' => 'Balikpapan',
            'kode_pos' => '76122',
            'alamat_sekretariat' => 'Jl. Sekretariat RT 32',
            'no_hp' => '081234567890',
            'publik_aktif' => true,
        ]);
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
}
