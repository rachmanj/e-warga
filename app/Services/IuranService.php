<?php

namespace App\Services;

use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\IuranTarif;
use App\Models\KasKategori;
use App\Models\KasTransaksi;
use App\Models\Keluarga;
use App\Models\Kwitansi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IuranService
{
    public function tarifEfektif(Keluarga $keluarga, IuranJenis $jenis): string
    {
        $khusus = IuranTarif::query()
            ->where('tenant_id', $keluarga->tenant_id)
            ->where('iuran_jenis_id', $jenis->id)
            ->where('keluarga_id', $keluarga->id)
            ->first();

        if ($khusus !== null) {
            return $this->formatUang($khusus->nominal);
        }

        return $this->formatUang($jenis->nominal_default);
    }

    /**
     * @return array{nominal: string, terbayar: string, sisa: string}
     */
    public function tagihanEfektif(IuranTagihan $tagihan): array
    {
        $nominal = $this->formatUang($tagihan->nominal);
        $terbayar = $this->formatUang(
            $tagihan->pembayaran()->sum('jumlah')
        );
        $sisa = $this->kurangi($nominal, $terbayar);

        if (bccomp($sisa, '0', 2) < 0) {
            $sisa = '0.00';
        }

        return [
            'nominal' => $nominal,
            'terbayar' => $terbayar,
            'sisa' => $sisa,
        ];
    }

    /**
     * @return array{dibuat: int, dilewati: int}
     */
    public function buatTagihan(IuranJenis $jenis, string $periode, ?string $jatuhTempo = null): array
    {
        $dibuat = 0;
        $dilewati = 0;

        $keluargaAktif = Keluarga::query()
            ->where('tenant_id', $jenis->tenant_id)
            ->where('status', 'aktif')
            ->get();

        foreach ($keluargaAktif as $keluarga) {
            $sudahAda = IuranTagihan::query()
                ->where('tenant_id', $jenis->tenant_id)
                ->where('keluarga_id', $keluarga->id)
                ->where('iuran_jenis_id', $jenis->id)
                ->where('periode', $periode)
                ->exists();

            if ($sudahAda) {
                $dilewati++;

                continue;
            }

            IuranTagihan::query()->create([
                'tenant_id' => $jenis->tenant_id,
                'keluarga_id' => $keluarga->id,
                'iuran_jenis_id' => $jenis->id,
                'periode' => $periode,
                'nominal' => $this->tarifEfektif($keluarga, $jenis),
                'jatuh_tempo' => $jatuhTempo,
                'status' => 'belum',
            ]);

            $dibuat++;
        }

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /**
     * @param  array{no_referensi?: string|null, bukti_path?: string|null}  $opsi
     */
    public function catatPembayaran(
        IuranTagihan $tagihan,
        string $tanggal,
        string $jumlah,
        string $metode,
        int $dicatatOleh,
        array $opsi = []
    ): IuranPembayaran {
        $jumlah = $this->formatUang($jumlah);
        $efektif = $this->tagihanEfektif($tagihan);

        if (bccomp($jumlah, $efektif['sisa'], 2) > 0) {
            throw new InvalidArgumentException('Jumlah pembayaran melebihi sisa tagihan.');
        }

        return DB::transaction(function () use ($tagihan, $tanggal, $jumlah, $metode, $dicatatOleh, $opsi, $efektif): IuranPembayaran {
            $pembayaran = IuranPembayaran::query()->create([
                'tenant_id' => $tagihan->tenant_id,
                'iuran_tagihan_id' => $tagihan->id,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah,
                'metode' => $metode,
                'no_referensi' => $opsi['no_referensi'] ?? null,
                'bukti_path' => $opsi['bukti_path'] ?? null,
                'dicatat_oleh' => $dicatatOleh,
            ]);

            $tahun = (int) date('Y', strtotime($tanggal));
            $nomor = $this->nomorKwitansiBerikutnya((int) $tagihan->tenant_id, $tahun);

            Kwitansi::query()->create([
                'tenant_id' => $tagihan->tenant_id,
                'iuran_pembayaran_id' => $pembayaran->id,
                'nomor' => $nomor,
                'tahun' => $tahun,
                'tanggal' => $tanggal,
            ]);

            $pos = $metode === 'tunai' ? 'tunai' : 'bank';
            $kategori = KasKategori::query()
                ->where('tenant_id', $tagihan->tenant_id)
                ->where('nama', 'Iuran')
                ->where('jenis', 'masuk')
                ->first();

            KasTransaksi::query()->create([
                'tenant_id' => $tagihan->tenant_id,
                'tanggal' => $tanggal,
                'jenis' => 'masuk',
                'pos' => $pos,
                'kas_kategori_id' => $kategori?->id,
                'uraian' => 'Pembayaran iuran tagihan #'.$tagihan->id,
                'jumlah' => $jumlah,
                'iuran_pembayaran_id' => $pembayaran->id,
                'created_by' => $dicatatOleh,
            ]);

            $terbayarBaru = $this->tambah($efektif['terbayar'], $jumlah);
            $tagihan->status = $this->statusDariTerbayar($efektif['nominal'], $terbayarBaru);
            $tagihan->save();

            return $pembayaran->fresh(['kwitansi', 'kasTransaksi']);
        });
    }

    public function hapusPembayaran(IuranPembayaran $pembayaran): void
    {
        DB::transaction(function () use ($pembayaran): void {
            $tagihan = $pembayaran->tagihan;

            KasTransaksi::query()
                ->where('iuran_pembayaran_id', $pembayaran->id)
                ->delete();

            Kwitansi::query()
                ->where('iuran_pembayaran_id', $pembayaran->id)
                ->delete();

            $pembayaran->delete();

            $tagihan->refresh();
            $efektif = $this->tagihanEfektif($tagihan);
            $tagihan->status = $this->statusDariTerbayar($efektif['nominal'], $efektif['terbayar']);
            $tagihan->save();
        });
    }

    private function nomorKwitansiBerikutnya(int $tenantId, int $tahun): string
    {
        $prefix = 'KW'.$tahun;

        $maxUrut = Kwitansi::query()
            ->where('tenant_id', $tenantId)
            ->where('tahun', $tahun)
            ->where('nomor', 'like', $prefix.'%')
            ->lockForUpdate()
            ->get()
            ->map(function (Kwitansi $k) use ($prefix): int {
                $suffix = substr($k->nomor, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })
            ->max();

        $urut = ($maxUrut ?? 0) + 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    private function statusDariTerbayar(string $nominal, string $terbayar): string
    {
        if (bccomp($terbayar, '0', 2) <= 0) {
            return 'belum';
        }

        if (bccomp($terbayar, $nominal, 2) >= 0) {
            return 'lunas';
        }

        return 'sebagian';
    }

    private function formatUang(mixed $nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }

    private function tambah(string $a, string $b): string
    {
        return $this->formatUang(bcadd($a, $b, 2));
    }

    private function kurangi(string $a, string $b): string
    {
        return $this->formatUang(bcsub($a, $b, 2));
    }
}
