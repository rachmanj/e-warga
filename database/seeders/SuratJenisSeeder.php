<?php

namespace Database\Seeders;

use App\Models\Rt;
use App\Models\SuratJenis;
use Illuminate\Database\Seeder;

class SuratJenisSeeder extends Seeder
{
    /**
     * @var list<array{kode: string, nama: string, urutan: int}>
     */
    private array $jenis = [
        ['kode' => 'SKD', 'nama' => 'Surat Keterangan Domisili', 'urutan' => 1],
        ['kode' => 'SKTM', 'nama' => 'Surat Keterangan Tidak Mampu', 'urutan' => 2],
        ['kode' => 'SKU', 'nama' => 'Surat Keterangan Usaha', 'urutan' => 3],
        ['kode' => 'SPSK', 'nama' => 'Surat Pengantar SKCK', 'urutan' => 4],
        ['kode' => 'SPN', 'nama' => 'Surat Pengantar Nikah', 'urutan' => 5],
    ];

    private string $templateBody = <<<'TXT'
Yang bertanda tangan di bawah ini, Ketua {{nama_rt}}, Kelurahan {{kelurahan}}, {{kota}}, menerangkan bahwa:

Nama          : {{nama}}
NIK           : {{nik_tersamar}}
Alamat        : {{alamat}}

Adalah benar warga kami dan berdomisili di alamat tersebut di atas.

Surat keterangan ini dibuat untuk keperluan: {{keperluan}}.

Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.

Nomor surat: {{nomor}}
{{kota}}, {{tanggal_terbit}}
TXT;

    public function run(): void
    {
        Rt::query()->each(function (Rt $rt): void {
            foreach ($this->jenis as $item) {
                SuratJenis::query()->withoutGlobalScope('tenant')->firstOrCreate(
                    [
                        'tenant_id' => $rt->id,
                        'kode' => $item['kode'],
                    ],
                    [
                        'nama' => $item['nama'],
                        'format_nomor' => '{urut}/{kode}/{rt}/{rw}/{tahun}',
                        'template_body' => $this->templateBody,
                        'butuh_data_warga' => true,
                        'aktif' => true,
                        'urutan' => $item['urutan'],
                    ]
                );
            }
        });
    }
}
