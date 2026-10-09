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

    private string $templateBodySuratPengantar = <<<'TXT'
Bersama ini kami mengantarkan warga {{nama_rt}} yang disebutkan dibawah ini :

Nama                  : {{nama}}
No. KTP               : {{nik}}
Jenis Kelamin         : {{pilihan_jenis_kelamin}}
Tempat/Tanggal Lahir  : {{tempat_tanggal_lahir}}
Status                : {{pilihan_status_perkawinan}}
Pendidikan            : {{pilihan_pendidikan}}
Agama                 : {{pilihan_agama}}
Pekerjaan             : {{pekerjaan}}
Kewarganegaraan       : {{pilihan_kewarganegaraan}}
Alamat Tinggal        : {{alamat}}
Golongan Darah        : {{pilihan_golongan_darah}}

Keperluan untuk mengurus:
{{daftar_keperluan}}

Sebagai pertimbangan diberitahukan bahwa yang bersangkutan :
{{pertimbangan_kk}}

Demikian Surat Pengantar ini dibuat agar dapat dipergunakan sebagaimana mestinya.
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

            $sp = SuratJenis::query()->withoutGlobalScope('tenant')->firstOrCreate(
                [
                    'tenant_id' => $rt->id,
                    'kode' => 'SP',
                ],
                [
                    'nama' => 'Surat Pengantar',
                    'format_nomor' => '{urut}/GSI-32/{tahun2}',
                    'template_body' => $this->templateBodySuratPengantar,
                    'butuh_data_warga' => true,
                    'aktif' => true,
                    'urutan' => 0,
                ]
            );

            $body = $sp->template_body ?? '';
            $sudahKalimatPembukaBaru = str_contains($body, 'Bersama ini kami mengantarkan warga');

            if (! $sudahKalimatPembukaBaru) {
                $masihTemplateLama = str_contains($body, 'Yang bertanda tangan di bawah ini')
                    || str_contains($body, 'Pertimbangan Kartu Keluarga')
                    || str_contains($body, '{{tempat_lahir}}')
                    || str_contains($body, '{{tanggal_lahir}}')
                    || str_contains($body, '{{nik_tersamar}}')
                    || ! str_contains($body, '{{daftar_keperluan}}')
                    || ! str_contains($body, '{{pertimbangan_kk}}');

                if ($masihTemplateLama) {
                    $sp->update(['template_body' => $this->templateBodySuratPengantar]);
                }
            }

            if ($sp->format_nomor === '{urut}/GSI-32/{tahun}') {
                $sp->update(['format_nomor' => '{urut}/GSI-32/{tahun2}']);
            }
        });
    }
}
