<?php

namespace App\Services;

use App\Models\IuranTagihan;
use App\Models\Keluarga;
use App\Models\Surat;
use App\Models\Warga;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private IuranService $iuranService,
        private KasService $kasService,
    ) {}

    /**
     * @return array{
     *     total_keluarga: int,
     *     total_jiwa: int,
     *     tunggakan_bulan_berjalan: array{jumlah_keluarga: int, total_sisa: string},
     *     surat_terbit_bulan_ini: int,
     *     saldo_kas_tunai: string,
     *     saldo_kas_bank: string,
     *     grafik_enam_bulan: list<array{label: string, periode: string, total_tagihan: string, total_terbayar: string}>
     * }
     */
    public function ringkasan(int $tenantId): array
    {
        $sekarang = Carbon::now();
        $tahun = (int) $sekarang->format('Y');
        $periodeBulanIni = $sekarang->format('Y-m');
        $periodeBulanLalu = $sekarang->copy()->subMonth()->format('Y-m');

        $totalKeluarga = Keluarga::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'aktif')
            ->count();

        $totalJiwa = Warga::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'aktif')
            ->whereHas('keluarga', fn ($q) => $q->where('status', 'aktif'))
            ->count();

        $tagihanTunggakan = IuranTagihan::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('periode', [$periodeBulanLalu, $periodeBulanIni])
            ->whereIn('status', ['belum', 'sebagian'])
            ->get();

        $keluargaMenunggak = [];
        foreach ($tagihanTunggakan as $tagihan) {
            $efektif = $this->iuranService->tagihanEfektif($tagihan);
            if (bccomp($efektif['sisa'], '0', 2) > 0) {
                $keluargaMenunggak[$tagihan->keluarga_id] = true;
            }
        }

        $ringkasanTunggakan = $this->iuranService->ringkasanPeriode(
            $tenantId,
            [$periodeBulanLalu, $periodeBulanIni],
            null,
            ['belum', 'sebagian']
        );

        $suratTerbitBulanIni = Surat::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'terbit')
            ->whereYear('tanggal_terbit', $tahun)
            ->whereMonth('tanggal_terbit', (int) $sekarang->format('m'))
            ->count();

        $saldoTunai = $this->kasService->ringkasan($tenantId, $tahun, 'tunai')['saldo_akhir'];
        $saldoBank = $this->kasService->ringkasan($tenantId, $tahun, 'bank')['saldo_akhir'];

        $labelBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $grafik = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = $sekarang->copy()->subMonths($i);
            $periode = $bulan->format('Y-m');
            $ringkasanBulan = $this->iuranService->ringkasanPeriode($tenantId, [$periode]);

            $grafik[] = [
                'label' => $labelBulan[(int) $bulan->format('n') - 1],
                'periode' => $periode,
                'total_tagihan' => $ringkasanBulan['total_tagihan'],
                'total_terbayar' => $ringkasanBulan['total_terbayar'],
            ];
        }

        $nilaiMaks = 0.0;
        foreach ($grafik as $baris) {
            $nilaiMaks = max($nilaiMaks, (float) $baris['total_tagihan'], (float) $baris['total_terbayar']);
        }

        foreach ($grafik as $indeks => $baris) {
            if ($nilaiMaks <= 0) {
                $grafik[$indeks]['tinggi_tagihan'] = 0;
                $grafik[$indeks]['tinggi_terbayar'] = 0;
            } else {
                $grafik[$indeks]['tinggi_tagihan'] = (int) round(((float) $baris['total_tagihan'] / $nilaiMaks) * 100);
                $grafik[$indeks]['tinggi_terbayar'] = (int) round(((float) $baris['total_terbayar'] / $nilaiMaks) * 100);
            }
        }

        return [
            'total_keluarga' => $totalKeluarga,
            'total_jiwa' => $totalJiwa,
            'tunggakan_bulan_berjalan' => [
                'jumlah_keluarga' => count($keluargaMenunggak),
                'total_sisa' => $ringkasanTunggakan['total_tunggakan'],
            ],
            'surat_terbit_bulan_ini' => $suratTerbitBulanIni,
            'saldo_kas_tunai' => $saldoTunai,
            'saldo_kas_bank' => $saldoBank,
            'grafik_enam_bulan' => $grafik,
        ];
    }
}
