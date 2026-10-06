<?php

namespace Database\Seeders;

use App\Models\KasKategori;
use App\Models\Rt;
use Illuminate\Database\Seeder;

class KasKategoriSeeder extends Seeder
{
    /**
     * @var list<array{nama: string, jenis: string}>
     */
    private array $kategoriMasuk = [
        ['nama' => 'Iuran', 'jenis' => 'masuk'],
        ['nama' => 'Sumbangan', 'jenis' => 'masuk'],
    ];

    /**
     * @var list<array{nama: string, jenis: string}>
     */
    private array $kategoriKeluar = [
        ['nama' => 'Kebersihan', 'jenis' => 'keluar'],
        ['nama' => 'Keamanan', 'jenis' => 'keluar'],
        ['nama' => 'Konsumsi Rapat', 'jenis' => 'keluar'],
        ['nama' => 'Perbaikan', 'jenis' => 'keluar'],
    ];

    public function run(): void
    {
        $semua = array_merge($this->kategoriMasuk, $this->kategoriKeluar);

        Rt::query()->each(function (Rt $rt) use ($semua): void {
            foreach ($semua as $item) {
                KasKategori::query()->firstOrCreate(
                    [
                        'tenant_id' => $rt->id,
                        'nama' => $item['nama'],
                        'jenis' => $item['jenis'],
                    ],
                    [
                        'tenant_id' => $rt->id,
                        'nama' => $item['nama'],
                        'jenis' => $item['jenis'],
                    ]
                );
            }
        });
    }
}
