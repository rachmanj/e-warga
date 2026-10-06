<?php

namespace App\Services;

use App\Models\Keluarga;
use App\Models\Rt;
use App\Models\Warga;
use Illuminate\Support\Facades\DB;

class Rt32WargaCsvImporter
{
    /**
     * @var list<string>
     */
    private const REQUIRED_HEADERS = [
        'baris', 'no_urut', 'no_kk', 'no_kk_16', 'rt', 'no_rumah', 'nama_kepala', 'no_hp',
        'nama_anggota', 'nik_16', 'nik_asli', 'jk', 'tanggal_lahir', 'jumlah_anggota',
        'tempat_lahir', 'gol_darah', 'grade', 'pendidikan', 'pekerjaan', 'agama', 'status_rumah',
        'bantuan', 'warna', 'is_kepala', 'keluarga_index', 'keluarga_no_kk', 'keluarga_nama_kepala',
        'keluarga_warna', 'keluarga_status_rumah',
    ];

    /**
     * @return array{
     *     keluarga_dibuat: int,
     *     keluarga_dilewati: int,
     *     warga_dibuat: int,
     *     warga_dilewati: int,
     *     nik_dikosongkan: int,
     *     jk_tidak_valid: int,
     *     kk_bukan_16_digit: int,
     *     kepala_dilewati: list<string>,
     *     mode: string,
     * }
     */
    public function import(string $filePath, int $tenantId, bool $execute): array
    {
        Rt::query()->findOrFail($tenantId);

        $families = $this->parseAndGroup($filePath);

        $stats = [
            'keluarga_dibuat' => 0,
            'keluarga_dilewati' => 0,
            'warga_dibuat' => 0,
            'warga_dilewati' => 0,
            'nik_dikosongkan' => 0,
            'jk_tidak_valid' => 0,
            'kk_bukan_16_digit' => 0,
            'kepala_dilewati' => [],
            'mode' => $execute ? 'execute' : 'dry-run',
        ];

        $process = function () use ($families, $tenantId, $execute, &$stats): void {
            foreach ($families as $family) {
                $this->processFamily($family, $tenantId, $execute, $stats);
            }
        };

        if ($execute) {
            DB::transaction($process);
        } else {
            $process();
        }

        return $stats;
    }

    /**
     * @return array{
     *     warga_diperbarui: int,
     *     warga_dilewati: int,
     *     warga_tidak_ditemukan: int,
     *     mode: string,
     * }
     */
    public function perbaruiProfil(string $filePath, int $tenantId, bool $execute): array
    {
        Rt::query()->findOrFail($tenantId);

        $families = $this->parseAndGroup($filePath);

        $stats = [
            'warga_diperbarui' => 0,
            'warga_dilewati' => 0,
            'warga_tidak_ditemukan' => 0,
            'mode' => $execute ? 'execute' : 'dry-run',
        ];

        $process = function () use ($families, $tenantId, $execute, &$stats): void {
            foreach ($families as $family) {
                $this->perbaruiProfilKeluarga($family, $tenantId, $execute, $stats);
            }
        };

        if ($execute) {
            DB::transaction($process);
        } else {
            $process();
        }

        return $stats;
    }

    /**
     * @param  array{
     *     keluarga_no_kk: string,
     *     keluarga_nama_kepala: string,
     *     members: list<array<string, string>>,
     * }  $family
     * @param  array<string, mixed>  $stats
     */
    private function perbaruiProfilKeluarga(array $family, int $tenantId, bool $execute, array &$stats): void
    {
        $keluarga = $this->findExistingKeluargaUntukProfil($tenantId, $family['keluarga_no_kk'], $family['keluarga_nama_kepala']);

        foreach ($family['members'] as $member) {
            $namaAnggota = trim($member['nama_anggota']);
            if ($namaAnggota === '') {
                continue;
            }

            if ($keluarga === null) {
                $stats['warga_tidak_ditemukan']++;

                continue;
            }

            $warga = $keluarga->warga()
                ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($namaAnggota)])
                ->first();

            if ($warga === null) {
                $stats['warga_tidak_ditemukan']++;

                continue;
            }

            $perubahan = $this->profilDariBarisCsv($member);
            if ($perubahan === []) {
                $stats['warga_dilewati']++;

                continue;
            }

            $adaPerubahan = false;
            foreach ($perubahan as $kolom => $nilai) {
                if ($warga->{$kolom} !== $nilai) {
                    $adaPerubahan = true;
                    break;
                }
            }

            if (! $adaPerubahan) {
                $stats['warga_dilewati']++;

                continue;
            }

            $stats['warga_diperbarui']++;

            if (! $execute) {
                continue;
            }

            $warga->fill($perubahan);
            $warga->save();
        }
    }

    /**
     * @param  array<string, string>  $member
     * @return array<string, string>
     */
    private function profilDariBarisCsv(array $member): array
    {
        $hasil = [];

        $tempatLahir = trim($member['tempat_lahir'] ?? '');
        if ($tempatLahir !== '') {
            $hasil['tempat_lahir'] = $tempatLahir;
        }

        $pendidikan = trim($member['pendidikan'] ?? '');
        if ($pendidikan !== '') {
            $hasil['pendidikan'] = $pendidikan;
        }

        $golDarah = trim($member['gol_darah'] ?? '');
        if ($golDarah !== '') {
            $hasil['golongan_darah'] = $golDarah;
        }

        $hasil['kewarganegaraan'] = 'WNI';

        return $hasil;
    }

    /**
     * @return array<int, array{
     *     keluarga_index: string,
     *     keluarga_no_kk: string,
     *     keluarga_nama_kepala: string,
     *     keluarga_warna: string,
     *     keluarga_status_rumah: string,
     *     no_rumah: string,
     *     members: list<array<string, string>>,
     * }>
     */
    private function parseAndGroup(string $filePath): array
    {
        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException("Berkas tidak dapat dibaca: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Gagal membuka berkas: {$filePath}");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \InvalidArgumentException('Berkas CSV kosong atau tidak valid.');
        }

        $header = array_map(fn (string $col): string => trim($col), $header);
        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $header, true)) {
                fclose($handle);
                throw new \InvalidArgumentException("Kolom wajib tidak ditemukan: {$required}");
            }
        }

        $families = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $record = [];
            foreach ($header as $index => $column) {
                $record[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            if ($record['nama_anggota'] === '' && $record['keluarga_index'] === '') {
                continue;
            }

            $index = $record['keluarga_index'];
            if ($index === '') {
                fclose($handle);
                throw new \InvalidArgumentException('Baris CSV tanpa keluarga_index.');
            }

            if (! isset($families[$index])) {
                $families[$index] = [
                    'keluarga_index' => $index,
                    'keluarga_no_kk' => $record['keluarga_no_kk'],
                    'keluarga_nama_kepala' => $record['keluarga_nama_kepala'],
                    'keluarga_warna' => $record['keluarga_warna'],
                    'keluarga_status_rumah' => $record['keluarga_status_rumah'],
                    'no_rumah' => $record['no_rumah'],
                    'members' => [],
                ];
            }

            if ($families[$index]['keluarga_no_kk'] === '' && $record['keluarga_no_kk'] !== '') {
                $families[$index]['keluarga_no_kk'] = $record['keluarga_no_kk'];
            }
            if ($families[$index]['keluarga_nama_kepala'] === '' && $record['keluarga_nama_kepala'] !== '') {
                $families[$index]['keluarga_nama_kepala'] = $record['keluarga_nama_kepala'];
            }
            if ($families[$index]['keluarga_warna'] === '' && $record['keluarga_warna'] !== '') {
                $families[$index]['keluarga_warna'] = $record['keluarga_warna'];
            }
            if ($families[$index]['keluarga_status_rumah'] === '' && $record['keluarga_status_rumah'] !== '') {
                $families[$index]['keluarga_status_rumah'] = $record['keluarga_status_rumah'];
            }
            if ($families[$index]['no_rumah'] === '' && $record['no_rumah'] !== '') {
                $families[$index]['no_rumah'] = $record['no_rumah'];
            }

            $families[$index]['members'][] = $record;
        }

        fclose($handle);

        ksort($families, SORT_NUMERIC);

        return $families;
    }

    /**
     * @param  array{
     *     keluarga_index: string,
     *     keluarga_no_kk: string,
     *     keluarga_nama_kepala: string,
     *     keluarga_warna: string,
     *     keluarga_status_rumah: string,
     *     no_rumah: string,
     *     members: list<array<string, string>>,
     * }  $family
     * @param  array<string, mixed>  $stats
     */
    private function processFamily(array $family, int $tenantId, bool $execute, array &$stats): void
    {
        $noKk = $family['keluarga_no_kk'];
        $namaKepala = $family['keluarga_nama_kepala'];

        if ($noKk !== '' && ! preg_match('/^\d{16}$/', $noKk)) {
            $stats['kk_bukan_16_digit']++;
        }

        $existing = $this->findExistingKeluarga($tenantId, $noKk, $namaKepala);

        if ($existing !== null) {
            $stats['keluarga_dilewati']++;
            $stats['warga_dilewati'] += count($family['members']);
            $stats['kepala_dilewati'][] = $namaKepala !== '' ? $namaKepala : '(tanpa nama kepala)';

            return;
        }

        $statusRumahAsli = $this->resolveStatusRumahAsli($family);
        $keluargaPayload = $this->buildKeluargaPayload($family, $statusRumahAsli);

        $wargaPayloads = [];
        foreach ($family['members'] as $member) {
            $wargaPayload = $this->buildWargaPayload($member, $stats);
            $wargaPayloads[] = $wargaPayload;
        }

        $stats['keluarga_dibuat']++;
        $stats['warga_dibuat'] += count($wargaPayloads);

        if (! $execute) {
            return;
        }

        $keluarga = new Keluarga($keluargaPayload);
        $keluarga->tenant_id = $tenantId;
        $keluarga->no_kk_hash = $this->resolveNoKkHash($noKk, $namaKepala);
        $keluarga->save();

        foreach ($wargaPayloads as $payload) {
            $warga = new Warga($payload);
            $warga->tenant_id = $tenantId;
            $warga->keluarga_id = $keluarga->id;
            $warga->save();
        }
    }

    private function findExistingKeluarga(int $tenantId, string $noKk, string $namaKepala): ?Keluarga
    {
        if ($noKk !== '') {
            $hash = $this->identifierHmac($noKk);

            return Keluarga::query()
                ->where('tenant_id', $tenantId)
                ->where('no_kk_hash', $hash)
                ->first();
        }

        if ($namaKepala === '') {
            return null;
        }

        return Keluarga::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('warga', function ($query) use ($namaKepala): void {
                $query->where('hubungan', 'kepala')->where('nama', $namaKepala);
            })
            ->first();
    }

    private function findExistingKeluargaUntukProfil(int $tenantId, string $noKk, string $namaKepala): ?Keluarga
    {
        if ($noKk !== '') {
            $hash = $this->identifierHmac($noKk);

            return Keluarga::query()
                ->where('tenant_id', $tenantId)
                ->where('no_kk_hash', $hash)
                ->first();
        }

        $namaKepala = trim($namaKepala);
        if ($namaKepala === '') {
            return null;
        }

        $namaNormal = mb_strtolower($namaKepala);

        return Keluarga::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('warga', function ($query) use ($namaNormal): void {
                $query
                    ->where('hubungan', 'kepala')
                    ->whereRaw('LOWER(TRIM(nama)) = ?', [$namaNormal]);
            })
            ->first();
    }

    private function resolveNoKkHash(string $noKk, string $namaKepala): string
    {
        if ($noKk !== '') {
            return $this->identifierHmac($noKk);
        }

        return $this->identifierHmac($namaKepala);
    }

    private function identifierHmac(string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key'));
    }

    /**
     * @param  array{
     *     keluarga_warna: string,
     *     keluarga_status_rumah: string,
     *     no_rumah: string,
     *     members: list<array<string, string>>,
     * }  $family
     * @return array<string, mixed>
     */
    private function buildKeluargaPayload(array $family, string $statusRumahAsli): array
    {
        $noRumah = $family['no_rumah'];
        $alamat = $noRumah !== '' ? "RT 32 No {$noRumah}" : 'RT 32';

        $keteranganParts = [];
        if ($statusRumahAsli !== '') {
            $keteranganParts[] = 'Status rumah (file): '.$statusRumahAsli;
        }

        $warna = strtolower(trim($family['keluarga_warna']));
        $status = 'aktif';

        if (in_array($warna, ['hijau', 'biru'], true)) {
            $status = 'nonaktif';
            $keteranganParts[] = 'Penanda file warna hijau/biru (warga bukan KK 32 atau sudah tinggal di luar)';
        } elseif ($warna === 'orange') {
            $keteranganParts[] = 'Penanda file warna orange (tinggal di luar tetapi masih ikut kegiatan)';
        } elseif (in_array($warna, ['kuning', 'pink'], true)) {
            $keteranganParts[] = 'Penanda file warna kuning/pink - perlu konfirmasi operator';
        }

        return [
            'no_kk' => $family['keluarga_no_kk'],
            'alamat' => $alamat,
            'blok_unit' => $noRumah !== '' ? $noRumah : null,
            'rt_lingkungan' => '32',
            'status_hunian' => $this->mapStatusHunian($statusRumahAsli),
            'status' => $status,
            'keterangan' => $keteranganParts !== [] ? implode(' ', $keteranganParts) : null,
        ];
    }

    /**
     * @param  array{
     *     keluarga_status_rumah: string,
     *     members: list<array<string, string>>,
     * }  $family
     */
    private function resolveStatusRumahAsli(array $family): string
    {
        if (trim($family['keluarga_status_rumah']) !== '') {
            return trim($family['keluarga_status_rumah']);
        }

        foreach ($family['members'] as $member) {
            if (trim($member['status_rumah'] ?? '') !== '') {
                return trim($member['status_rumah']);
            }
        }

        return '';
    }

    private function mapStatusHunian(string $statusRumah): string
    {
        $upper = mb_strtoupper($statusRumah);

        if (str_contains($upper, 'SENDIRI')) {
            return 'milik';
        }

        if (str_contains($upper, 'SEWA')) {
            return 'sewa';
        }

        if (str_contains($upper, 'KOS')) {
            return 'kos';
        }

        return 'kontrak';
    }

    /**
     * @param  array<string, string>  $member
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    private function buildWargaPayload(array $member, array &$stats): array
    {
        $jkRaw = strtoupper(trim($member['jk']));
        $jenisKelamin = 'L';
        $catatanParts = [];

        if ($jkRaw === 'L' || $jkRaw === 'P') {
            $jenisKelamin = $jkRaw;
        } else {
            $stats['jk_tidak_valid']++;
            if ($jkRaw !== '') {
                $catatanParts[] = "JK di file: {$member['jk']}";
            }
        }

        $nik = null;
        $nik16 = trim($member['nik_16']);
        if (preg_match('/^\d{16}$/', $nik16)) {
            $nik = $nik16;
        } else {
            $stats['nik_dikosongkan']++;
            $catatanParts[] = 'NIK di file tidak valid atau kosong';
            if (trim($member['nik_asli'] ?? '') !== '') {
                $catatanParts[] = 'NIK asli file: '.$member['nik_asli'];
            }
        }

        foreach (['tempat_lahir', 'gol_darah', 'pendidikan', 'grade', 'bantuan'] as $field) {
            $value = trim($member[$field] ?? '');
            if ($value !== '') {
                $catatanParts[] = $value;
            }
        }

        $tanggalLahir = trim($member['tanggal_lahir']);
        if ($tanggalLahir === '') {
            $tanggalLahir = null;
        }

        $isKepala = in_array(strtolower(trim($member['is_kepala'])), ['true', '1', 'yes'], true);

        return [
            'nama' => $member['nama_anggota'],
            'hubungan' => $isKepala ? 'kepala' : 'lain',
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir,
            'nik' => $nik,
            'no_hp' => trim($member['no_hp']) !== '' ? trim($member['no_hp']) : null,
            'pekerjaan' => trim($member['pekerjaan']) !== '' ? trim($member['pekerjaan']) : null,
            'agama' => trim($member['agama']) !== '' ? trim($member['agama']) : null,
            'status' => 'aktif',
            'catatan' => $catatanParts !== [] ? implode('; ', $catatanParts) : null,
        ];
    }
}
