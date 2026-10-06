<?php

namespace App\Services;

use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;

class KasService
{
    public function saldoAkhir(int $tenantId, int $tahun, string $pos): string
    {
        $ringkasan = $this->ringkasan($tenantId, $tahun, $pos);

        return $ringkasan['saldo_akhir'];
    }

    /**
     * @return array{saldo_awal: string, total_masuk: string, total_keluar: string, saldo_akhir: string}
     */
    public function ringkasan(int $tenantId, int $tahun, string $pos): array
    {
        $saldoAwal = $this->saldoAwalUntuk($tenantId, $tahun, $pos);
        $totalMasuk = $this->totalTransaksi($tenantId, $tahun, $pos, 'masuk');
        $totalKeluar = $this->totalTransaksi($tenantId, $tahun, $pos, 'keluar');
        $saldoAkhir = $this->kurangi($this->tambah($saldoAwal, $totalMasuk), $totalKeluar);

        return [
            'saldo_awal' => $saldoAwal,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo_akhir' => $saldoAkhir,
        ];
    }

    /**
     * @return list<array{bulan: int, total_masuk: string, total_keluar: string, saldo: string}>
     */
    /**
     * @return array{
     *     ringkasan: array{saldo_awal: string, total_masuk: string, total_keluar: string, saldo_akhir: string},
     *     baris: list<array<string, mixed>>
     * }
     */
    public function bukuKas(int $tenantId, int $tahun, string $pos): array
    {
        $ringkasan = $this->ringkasan($tenantId, $tahun, $pos);
        $saldoBerjalan = $ringkasan['saldo_awal'];

        $baris = [
            [
                'jenis_baris' => 'saldo_awal',
                'tanggal' => null,
                'uraian' => 'Saldo awal',
                'jenis' => null,
                'jumlah' => $ringkasan['saldo_awal'],
                'saldo_berjalan' => $saldoBerjalan,
                'id' => null,
            ],
        ];

        $transaksi = KasTransaksi::query()
            ->where('tenant_id', $tenantId)
            ->where('pos', $pos)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($transaksi as $row) {
            $jumlah = $this->formatUang($row->jumlah);
            if ($row->jenis === 'masuk') {
                $saldoBerjalan = $this->tambah($saldoBerjalan, $jumlah);
            } else {
                $saldoBerjalan = $this->kurangi($saldoBerjalan, $jumlah);
            }

            $baris[] = [
                'jenis_baris' => 'transaksi',
                'tanggal' => $row->tanggal,
                'uraian' => $row->uraian,
                'jenis' => $row->jenis,
                'jumlah' => $jumlah,
                'saldo_berjalan' => $saldoBerjalan,
                'id' => $row->id,
                'iuran_pembayaran_id' => $row->iuran_pembayaran_id,
            ];
        }

        return [
            'ringkasan' => $ringkasan,
            'baris' => $baris,
        ];
    }

    public function rekapBulanan(int $tenantId, int $tahun, string $pos): array
    {
        $saldoBerjalan = $this->saldoAwalUntuk($tenantId, $tahun, $pos);
        $hasil = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $totalMasuk = $this->totalTransaksiBulan($tenantId, $tahun, $pos, 'masuk', $bulan);
            $totalKeluar = $this->totalTransaksiBulan($tenantId, $tahun, $pos, 'keluar', $bulan);
            $saldoBerjalan = $this->kurangi($this->tambah($saldoBerjalan, $totalMasuk), $totalKeluar);

            $hasil[] = [
                'bulan' => $bulan,
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'saldo' => $saldoBerjalan,
            ];
        }

        return $hasil;
    }

    private function saldoAwalUntuk(int $tenantId, int $tahun, string $pos): string
    {
        $row = KasSaldoAwal::query()
            ->where('tenant_id', $tenantId)
            ->where('tahun', $tahun)
            ->where('pos', $pos)
            ->first();

        return $this->formatUang($row?->jumlah ?? '0');
    }

    private function totalTransaksi(int $tenantId, int $tahun, string $pos, string $jenis): string
    {
        $sum = KasTransaksi::query()
            ->where('tenant_id', $tenantId)
            ->where('pos', $pos)
            ->where('jenis', $jenis)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        return $this->formatUang($sum);
    }

    private function totalTransaksiBulan(int $tenantId, int $tahun, string $pos, string $jenis, int $bulan): string
    {
        $sum = KasTransaksi::query()
            ->where('tenant_id', $tenantId)
            ->where('pos', $pos)
            ->where('jenis', $jenis)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->sum('jumlah');

        return $this->formatUang($sum);
    }

    private function tambah(string $a, string $b): string
    {
        return $this->formatUang(bcadd($a, $b, 2));
    }

    private function kurangi(string $a, string $b): string
    {
        return $this->formatUang(bcsub($a, $b, 2));
    }

    private function formatUang(mixed $nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }
}
