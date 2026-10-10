<?php

namespace App\Services;

use App\Models\Rt;
use App\Models\Surat;
use App\Models\SuratNomorCounter;
use App\Models\Warga;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratService
{
    /**
     * @var array<string, string>
     */
    public const KEPERLUAN_SP = [
        'ktp' => 'Kartu Tanda Penduduk (KTP)',
        'kk' => 'Kartu Keluarga (KK)',
        'akta_kelahiran_anak' => 'Akta Kelahiran Anak',
        'pengantar_nikah' => 'Pengantar Nikah',
        'akta_kematian' => 'Akta Kematian',
        'akta_kelahiran' => 'Akta Kelahiran',
        'akta_pengesahan_anak' => 'Akta Pengesahan Anak',
        'keterangan_domisili' => 'Keterangan Domisili',
        'keterangan_pindah' => 'Keterangan Pindah',
        'keterangan_bepergian' => 'Keterangan Bepergian',
        'skck' => 'Surat Keterangan Kelakuan Baik (SKCK)',
        'keterangan_pemakaman' => 'Surat Keterangan Pemakaman',
    ];

    /**
     * @var list<string>
     */
    private const PLACEHOLDER_HTML = [
        'pilihan_jenis_kelamin',
        'pilihan_status_perkawinan',
        'pilihan_pendidikan',
        'pilihan_agama',
        'pilihan_kewarganegaraan',
        'pilihan_golongan_darah',
        'daftar_keperluan',
        'pertimbangan_kk',
    ];

    public function buatKodeVerifikasi(): string
    {
        do {
            $kode = Str::upper(Str::random(12));
        } while (Surat::query()->withoutGlobalScope('tenant')->where('kode_verifikasi', $kode)->exists());

        return $kode;
    }

    public function nomorBerikutnya(int $tenantId, int $tahun): int
    {
        return (int) DB::transaction(function () use ($tenantId, $tahun): int {
            $counter = SuratNomorCounter::query()
                ->where('tenant_id', $tenantId)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                SuratNomorCounter::query()->create([
                    'tenant_id' => $tenantId,
                    'tahun' => $tahun,
                    'nomor_terakhir' => 0,
                ]);

                $counter = SuratNomorCounter::query()
                    ->where('tenant_id', $tenantId)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $counter->nomor_terakhir = $counter->nomor_terakhir + 1;
            $counter->save();

            return $counter->nomor_terakhir;
        });
    }

    public function terbitkan(Surat $surat): Surat
    {
        if ($surat->isTerbit()) {
            return $surat;
        }

        $surat->loadMissing(['jenis', 'warga', 'keluarga']);

        $rt = Rt::query()->findOrFail($surat->tenant_id);
        $tahun = (int) now()->format('Y');

        $attempts = 0;
        while ($attempts < 5) {
            try {
                DB::transaction(function () use ($surat, $rt, $tahun): void {
                    $surat->refresh();

                    if ($surat->isTerbit()) {
                        return;
                    }

                    $urut = $this->nomorBerikutnya(
                        (int) $surat->tenant_id,
                        $tahun
                    );

                    $nomorLengkap = $this->formatNomor($surat, $rt, $urut, $tahun);

                    $surat->nomor_urut = $urut;
                    $surat->tahun = $tahun;
                    $surat->nomor_lengkap = $nomorLengkap;
                    $surat->status = 'terbit';
                    $surat->tanggal_terbit = now()->toDateString();

                    if ($surat->kode_verifikasi === null || $surat->kode_verifikasi === '') {
                        $surat->kode_verifikasi = $this->buatKodeVerifikasi();
                    }

                    $path = $this->simpanPdf($surat, $rt);
                    $surat->file_path = $path;
                    $surat->save();
                });

                break;
            } catch (QueryException $exception) {
                if (! $this->isNomorDuplikat($exception)) {
                    throw $exception;
                }
                $attempts++;
            }
        }

        return $surat->fresh(['jenis', 'warga', 'keluarga', 'catatan.pengguna']);
    }

    public function tolak(Surat $surat, string $alasan): Surat
    {
        if ($surat->isTerbit()) {
            return $surat;
        }

        $surat->update([
            'status' => 'ditolak',
            'alasan_tolak' => $alasan,
        ]);

        return $surat->fresh();
    }

    /**
     * @return array<string, string>
     */
    public function placeholderValues(Surat $surat, Rt $rt): array
    {
        $surat->loadMissing(['warga', 'keluarga', 'jenis']);

        $warga = $surat->warga;
        $keluarga = $surat->keluarga;

        $tanggalTerbit = $surat->tanggal_terbit
            ? $surat->tanggal_terbit->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        $nik = $warga?->nik;
        $nikLengkap = ($nik !== null && $nik !== '') ? $nik : str_repeat('.', 16);

        $noKk = $keluarga?->no_kk;
        $noKkTeks = ($noKk !== null && $noKk !== '') ? $noKk : '................';

        $keperluanTerpilih = $this->keperluanSpTerpilih($surat);

        return [
            'nomor' => $surat->nomor_lengkap ?? '—',
            'nama' => $warga?->nama ?? '—',
            'nik_tersamar' => $warga?->nik_tersamar ?? '—',
            'nik' => $nikLengkap,
            'tempat_tanggal_lahir' => $this->formatTempatTanggalLahir($warga),
            'no_kk' => $noKkTeks,
            'alamat' => $keluarga?->alamat ?? '—',
            'pekerjaan' => $warga?->pekerjaan ?? '—',
            'keperluan' => $surat->keperluan,
            'tanggal_terbit' => $tanggalTerbit,
            'nama_rt' => $rt->nama,
            'kelurahan' => $rt->kelurahan,
            'kota' => $rt->kota,
            'pilihan_jenis_kelamin' => $this->pilihanJenisKelamin($warga?->jenis_kelamin),
            'pilihan_status_perkawinan' => $this->pilihanStatusPerkawinan($warga?->status_perkawinan),
            'pilihan_pendidikan' => $this->pilihanPendidikan($warga?->pendidikan),
            'pilihan_agama' => $this->pilihanAgama($warga?->agama),
            'pilihan_kewarganegaraan' => $this->pilihanKewarganegaraan($warga?->kewarganegaraan),
            'pilihan_golongan_darah' => $this->pilihanGolonganDarah($warga?->golongan_darah),
            'daftar_keperluan' => $this->daftarKeperluan($keperluanTerpilih),
            'pertimbangan_kk' => $this->pertimbanganKk($noKk),
        ];
    }

    public function renderTemplate(Surat $surat, Rt $rt): string
    {
        $template = $surat->jenis?->template_body ?? '';
        $values = $this->placeholderValues($surat, $rt);

        $isi = $template;
        foreach ($values as $key => $value) {
            $replacement = in_array($key, self::PLACEHOLDER_HTML, true) ? $value : e($value);
            $isi = str_replace('{{'.$key.'}}', $replacement, $isi);
        }

        return nl2br($isi);
    }

    public function simpanPdf(Surat $surat, ?Rt $rt = null): string
    {
        $rt = $rt ?? Rt::query()->findOrFail($surat->tenant_id);
        $surat->loadMissing(['jenis', 'warga', 'keluarga']);

        $isi = $this->renderTemplate($surat, $rt);
        $verifikasiUrl = url('/verifikasi/'.$surat->kode_verifikasi);

        $pdf = Pdf::loadView('surat.pdf', [
            'surat' => $surat,
            'rt' => $rt,
            'isi' => $isi,
            'verifikasiUrl' => $verifikasiUrl,
        ]);

        $folder = 'surat/'.$surat->tahun;
        $filename = 'surat-'.$surat->id.'-'.Str::lower($surat->kode_verifikasi).'.pdf';
        $path = $folder.'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * @return list<string>
     */
    public function keperluanSpTerpilih(Surat $surat): array
    {
        $data = $surat->data_tambahan ?? [];
        $kodes = $data['keperluan'] ?? [];

        if (! is_array($kodes)) {
            return [];
        }

        return array_values(array_filter(
            $kodes,
            fn ($kode) => is_string($kode) && array_key_exists($kode, self::KEPERLUAN_SP)
        ));
    }

    /**
     * @param  list<string>  $labels
     */
    private function formatPilihanBerurutan(array $labels, ?int $selectedIndex): string
    {
        $parts = [];
        foreach ($labels as $index => $label) {
            $nomor = $index + 1;
            if ($selectedIndex === $index) {
                $parts[] = $nomor.'. <strong>✓ '.$label.'</strong>';
            } else {
                $parts[] = $nomor.'. '.$label;
            }
        }

        return implode(' ', $parts);
    }

    private function pilihanJenisKelamin(?string $jk): string
    {
        $index = match (strtoupper((string) $jk)) {
            'L', 'LAKI-LAKI', 'LAKI LAKI' => 0,
            'P', 'PEREMPUAN' => 1,
            default => null,
        };

        return $this->formatPilihanBerurutan(['Laki-laki', 'Perempuan'], $index);
    }

    private function pilihanStatusPerkawinan(?string $status): string
    {
        $normalized = Str::lower(trim((string) $status));
        $index = null;

        if ($normalized !== '') {
            if (str_contains($normalized, 'belum')) {
                $index = 0;
            } elseif ($normalized === 'kawin' || str_contains($normalized, 'menikah')) {
                $index = 1;
            } elseif (str_contains($normalized, 'duda')) {
                $index = 2;
            } elseif (str_contains($normalized, 'janda')) {
                $index = 3;
            }
        }

        return $this->formatPilihanBerurutan(['Belum Kawin', 'Kawin', 'Duda', 'Janda'], $index);
    }

    private function pilihanPendidikan(?string $pendidikan): string
    {
        $index = $this->indeksPendidikan($pendidikan);

        return $this->formatPilihanBerurutan(['SD', 'SLTP', 'SLTA', 'Universitas atau Akademi'], $index);
    }

    private function indeksPendidikan(?string $pendidikan): ?int
    {
        $normalized = Str::upper(trim((string) $pendidikan));
        if ($normalized === '') {
            return null;
        }

        if ($normalized === 'SD' || str_starts_with($normalized, 'SD ')) {
            return 0;
        }

        if (in_array($normalized, ['SMP', 'SLTP'], true) || str_contains($normalized, 'SLTP') || str_contains($normalized, 'SMP')) {
            return 1;
        }

        if (in_array($normalized, ['SMA', 'SMK', 'SLTA'], true)
            || str_contains($normalized, 'SLTA')
            || str_contains($normalized, 'SMA')
            || str_contains($normalized, 'SMK')) {
            return 2;
        }

        $tinggi = ['D3', 'D4', 'S1', 'S2', 'S3'];
        foreach ($tinggi as $gelar) {
            if ($normalized === $gelar || str_starts_with($normalized, $gelar.' ')) {
                return 3;
            }
        }

        if (str_contains($normalized, 'SARJANA')) {
            return 3;
        }

        return null;
    }

    private function pilihanAgama(?string $agama): string
    {
        $normalized = Str::lower(trim((string) $agama));
        $index = null;

        if ($normalized !== '') {
            if (str_contains($normalized, 'islam')) {
                $index = 0;
            } elseif (str_contains($normalized, 'protestan')) {
                $index = 1;
            } elseif (str_contains($normalized, 'katolik')) {
                $index = 2;
            } elseif (str_contains($normalized, 'hindu')) {
                $index = 3;
            } elseif (str_contains($normalized, 'budha') || str_contains($normalized, 'buddha')) {
                $index = 4;
            }
        }

        return $this->formatPilihanBerurutan(['Islam', 'Protestan', 'Katolik', 'Hindu', 'Budha'], $index);
    }

    private function pilihanKewarganegaraan(?string $kewarganegaraan): string
    {
        $normalized = Str::upper(trim((string) $kewarganegaraan));
        $index = 0;
        if ($normalized === 'WNA') {
            $index = 1;
        }

        return $this->formatPilihanBerurutan(['WNI', 'WNA'], $index);
    }

    private function pilihanGolonganDarah(?string $golonganDarah): string
    {
        $normalized = Str::upper(trim((string) $golonganDarah));
        $opsi = ['A', 'B', 'AB', 'O'];
        $parts = [];

        foreach ($opsi as $gol) {
            if ($normalized === $gol) {
                $parts[] = '<strong>✓ '.$gol.'</strong>';
            } else {
                $parts[] = $gol;
            }
        }

        return implode(' / ', $parts);
    }

    /**
     * @param  list<string>  $terpilih
     */
    private function daftarKeperluan(array $terpilih): string
    {
        $baris = [];
        $nomor = 1;
        foreach (self::KEPERLUAN_SP as $kode => $label) {
            if (in_array($kode, $terpilih, true)) {
                $baris[] = $nomor.'. <strong>✓ '.$label.'</strong>';
            } else {
                $baris[] = $nomor.'. '.$label;
            }
            $nomor++;
        }

        return implode("\n", $baris);
    }

    private function pertimbanganKk(?string $noKk): string
    {
        $punyaKk = $noKk !== null && $noKk !== '';

        $baris1 = $punyaKk
            ? '................................................................................'
            : 'Belum mempunyai / belum terdaftar dalam Kartu Keluarga';

        $baris2 = $punyaKk
            ? 'Sudah terdaftar dalam Kartu Keluarga No. <strong>'.e($noKk).'</strong>'
            : '................................................................................';

        $baris3 = '................................................................................';

        return $baris1."\n".$baris2."\n".$baris3;
    }

    private function formatTempatTanggalLahir(?Warga $warga): string
    {
        $tempat = trim((string) ($warga?->tempat_lahir ?? ''));
        $tanggal = $warga?->tanggal_lahir;

        if ($tanggal === null) {
            return $tempat !== '' ? $tempat : '—';
        }

        $tanggalTeks = $tanggal->translatedFormat('d F Y');

        if ($tempat === '') {
            return $tanggalTeks;
        }

        return $tempat.', '.$tanggalTeks;
    }

    private function formatNomor(Surat $surat, Rt $rt, int $urut, int $tahun): string
    {
        $format = $surat->jenis?->format_nomor ?? '{urut}/{kode}/{rt}/{rw}/{tahun}';

        $replacements = [
            '{urut}' => str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            '{kode}' => $surat->jenis?->kode ?? '',
            '{rt}' => $rt->nama,
            '{rw}' => $rt->rw,
            '{tahun}' => (string) $tahun,
            '{tahun2}' => sprintf('%02d', $tahun % 100),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    private function isNomorDuplikat(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'surat_tenant_id_nomor_urut_tahun_unique')
            || str_contains($message, 'surat_tenant_id_surat_jenis_id_nomor_urut_tahun_unique')
            || str_contains($message, 'surat.tenant_id')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}
