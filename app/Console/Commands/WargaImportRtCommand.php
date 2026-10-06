<?php

namespace App\Console\Commands;

use App\Services\Rt32WargaCsvImporter;
use Illuminate\Console\Command;

class WargaImportRtCommand extends Command
{
    protected $signature = 'warga:import-rt
                            {--file= : Path berkas CSV}
                            {--tenant= : ID tenant RT}
                            {--dry-run : Simulasi tanpa menulis ke database}
                            {--execute : Jalankan impor ke database}
                            {--force : Wajib bersama --execute untuk menulis data}
                            {--perbarui-profil : Lengkapi profil warga yang sudah ada tanpa membuat keluarga/warga baru}';

    protected $description = 'Impor data warga RT 32 dari berkas CSV';

    public function handle(Rt32WargaCsvImporter $importer): int
    {
        $file = $this->option('file');
        $tenant = $this->option('tenant');

        if (! is_string($file) || $file === '') {
            $this->error('Opsi --file wajib diisi.');

            return self::FAILURE;
        }

        if ($tenant === null || $tenant === '' || ! ctype_digit((string) $tenant)) {
            $this->error('Opsi --tenant wajib berisi ID numerik.');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $perbaruiProfil = (bool) $this->option('perbarui-profil');

        if ($execute && ! $force) {
            $this->error('Impor ke database memerlukan --execute dan --force bersamaan. Tanpa --force, tidak ada data yang ditulis.');

            return self::FAILURE;
        }

        if ($execute && $force && $dryRun) {
            $this->error('Gunakan salah satu: --dry-run atau --execute --force.');

            return self::FAILURE;
        }

        $willExecute = $execute && $force;

        try {
            if ($perbaruiProfil) {
                $stats = $importer->perbaruiProfil($file, (int) $tenant, $willExecute);
            } else {
                $stats = $importer->import($file, (int) $tenant, $willExecute);
            }
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($perbaruiProfil) {
            $modeLabel = $willExecute ? 'Eksekusi perbarui profil' : 'Dry-run perbarui profil (rencana)';
            $this->line($modeLabel);
            $this->line('Warga diperbarui: '.$stats['warga_diperbarui']);
            $this->line('Warga dilewati: '.$stats['warga_dilewati']);
            $this->line('Warga tidak ditemukan: '.$stats['warga_tidak_ditemukan']);

            return self::SUCCESS;
        }

        $modeLabel = $willExecute ? 'Eksekusi' : 'Dry-run (rencana)';
        $this->line($modeLabel);
        $this->line('Keluarga dibuat: '.$stats['keluarga_dibuat']);
        $this->line('Keluarga dilewati: '.$stats['keluarga_dilewati']);
        $this->line('Warga dibuat: '.$stats['warga_dibuat']);
        $this->line('Warga dilewati: '.$stats['warga_dilewati']);
        $this->line('NIK dikosongkan: '.$stats['nik_dikosongkan']);
        $this->line('Warga tanpa jenis kelamin valid di file: '.$stats['jk_tidak_valid']);
        $this->line('Keluarga dengan nomor KK bukan 16 digit: '.$stats['kk_bukan_16_digit']);

        if ($stats['kepala_dilewati'] !== []) {
            $this->line('Kepala keluarga dilewati:');
            foreach ($stats['kepala_dilewati'] as $nama) {
                $this->line('  - '.$nama);
            }
        }

        return self::SUCCESS;
    }
}
