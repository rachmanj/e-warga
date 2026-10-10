<?php

namespace App\Console\Commands;

use App\Models\SuratNomorCounter;
use Illuminate\Console\Command;

class SetSuratNomorTerakhirCommand extends Command
{
    protected $signature = 'surat:set-nomor-terakhir
                            {--tenant= : ID tenant RT}
                            {--tahun= : Tahun penomoran}
                            {--terakhir= : Nilai nomor_terakhir yang disimpan}';

    protected $description = 'Menetapkan titik awal penghitung nomor surat per tenant dan tahun';

    public function handle(): int
    {
        $tenant = $this->option('tenant');
        $tahun = $this->option('tahun');
        $terakhir = $this->option('terakhir');

        if ($tenant === null || $tenant === '' || ! ctype_digit((string) $tenant)) {
            $this->error('Opsi --tenant wajib berisi ID numerik.');

            return self::FAILURE;
        }

        if ($tahun === null || $tahun === '' || ! ctype_digit((string) $tahun)) {
            $this->error('Opsi --tahun wajib berisi tahun numerik.');

            return self::FAILURE;
        }

        if ($terakhir === null || $terakhir === '' || ! ctype_digit((string) $terakhir)) {
            $this->error('Opsi --terakhir wajib berisi bilangan bulat non-negatif.');

            return self::FAILURE;
        }

        $tenantId = (int) $tenant;
        $tahunInt = (int) $tahun;
        $terakhirInt = (int) $terakhir;

        if ($terakhirInt < 0) {
            $this->error('Opsi --terakhir tidak boleh negatif.');

            return self::FAILURE;
        }

        $counter = SuratNomorCounter::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'tahun' => $tahunInt,
            ],
            [
                'nomor_terakhir' => 0,
            ]
        );

        $sebelum = $counter->nomor_terakhir;
        $counter->nomor_terakhir = $terakhirInt;
        $counter->save();

        $this->line('Penghitung nomor surat diperbarui.');
        $this->line("Tenant: {$tenantId}, tahun: {$tahunInt}");
        $this->line("nomor_terakhir sebelum: {$sebelum}");
        $this->line("nomor_terakhir sesudah: {$counter->nomor_terakhir}");

        return self::SUCCESS;
    }
}
