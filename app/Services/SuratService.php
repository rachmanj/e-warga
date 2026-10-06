<?php

namespace App\Services;

use App\Models\Rt;
use App\Models\Surat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratService
{
    public function buatKodeVerifikasi(): string
    {
        do {
            $kode = Str::upper(Str::random(12));
        } while (Surat::query()->withoutGlobalScope('tenant')->where('kode_verifikasi', $kode)->exists());

        return $kode;
    }

    public function nomorBerikutnya(int $tenantId, int $suratJenisId, int $tahun): int
    {
        return (int) DB::transaction(function () use ($tenantId, $suratJenisId, $tahun): int {
            $max = Surat::query()
                ->where('tenant_id', $tenantId)
                ->where('surat_jenis_id', $suratJenisId)
                ->where('tahun', $tahun)
                ->whereNotNull('nomor_urut')
                ->lockForUpdate()
                ->max('nomor_urut');

            return ($max ?? 0) + 1;
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
                        (int) $surat->surat_jenis_id,
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
        $surat->loadMissing(['warga', 'keluarga']);

        $tanggalTerbit = $surat->tanggal_terbit
            ? $surat->tanggal_terbit->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        return [
            'nomor' => $surat->nomor_lengkap ?? '—',
            'nama' => $surat->warga?->nama ?? '—',
            'nik_tersamar' => $surat->warga?->nik_tersamar ?? '—',
            'alamat' => $surat->keluarga?->alamat ?? '—',
            'keperluan' => $surat->keperluan,
            'tanggal_terbit' => $tanggalTerbit,
            'nama_rt' => $rt->nama,
            'kelurahan' => $rt->kelurahan,
            'kota' => $rt->kota,
        ];
    }

    public function renderTemplate(Surat $surat, Rt $rt): string
    {
        $template = $surat->jenis?->template_body ?? '';
        $values = $this->placeholderValues($surat, $rt);

        $isi = $template;
        foreach ($values as $key => $value) {
            $isi = str_replace('{{'.$key.'}}', e($value), $isi);
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

    private function formatNomor(Surat $surat, Rt $rt, int $urut, int $tahun): string
    {
        $format = $surat->jenis?->format_nomor ?? '{urut}/{kode}/{rt}/{rw}/{tahun}';

        $replacements = [
            '{urut}' => str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            '{kode}' => $surat->jenis?->kode ?? '',
            '{rt}' => $rt->nama,
            '{rw}' => $rt->rw,
            '{tahun}' => (string) $tahun,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    private function isNomorDuplikat(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'surat_tenant_id_surat_jenis_id_nomor_urut_tahun_unique')
            || str_contains($message, 'surat.tenant_id')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}
