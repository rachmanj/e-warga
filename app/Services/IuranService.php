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

    /**
     * @return array{total_tagihan: string, total_terbayar: string, total_tunggakan: string}
     */
    public function ringkasanTahun(int $tenantId, int $tahun, ?int $iuranJenisId = null): array
    {
        $query = IuranTagihan::query()
            ->where('tenant_id', $tenantId)
            ->where('periode', 'like', $tahun.'-%');

        if ($iuranJenisId !== null) {
            $query->where('iuran_jenis_id', $iuranJenisId);
        }

        $totalTagihan = '0.00';
        $totalTerbayar = '0.00';
        $totalTunggakan = '0.00';

        foreach ($query->get() as $tagihan) {
            $efektif = $this->tagihanEfektif($tagihan);
            $totalTagihan = $this->tambah($totalTagihan, $efektif['nominal']);
            $totalTerbayar = $this->tambah($totalTerbayar, $efektif['terbayar']);
            if ($tagihan->status !== 'bebas') {
                $totalTunggakan = $this->tambah($totalTunggakan, $efektif['sisa']);
            }
        }

        return [
            'total_tagihan' => $totalTagihan,
            'total_terbayar' => $totalTerbayar,
            'total_tunggakan' => $totalTunggakan,
        ];
    }

    /**
     * @param  list<string>  $periodes
     * @param  list<string>|null  $status
     * @return array{total_tagihan: string, total_terbayar: string, total_tunggakan: string}
     */
    public function ringkasanPeriode(int $tenantId, array $periodes, ?int $iuranJenisId = null, ?array $status = null): array
    {
        if ($periodes === []) {
            return [
                'total_tagihan' => '0.00',
                'total_terbayar' => '0.00',
                'total_tunggakan' => '0.00',
            ];
        }

        $query = IuranTagihan::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('periode', $periodes);

        if ($status !== null) {
            $query->whereIn('status', $status);
        }

        if ($iuranJenisId !== null) {
            $query->where('iuran_jenis_id', $iuranJenisId);
        }

        $totalTagihan = '0.00';
        $totalTerbayar = '0.00';
        $totalTunggakan = '0.00';

        foreach ($query->get() as $tagihan) {
            $efektif = $this->tagihanEfektif($tagihan);
            $totalTagihan = $this->tambah($totalTagihan, $efektif['nominal']);
            $totalTerbayar = $this->tambah($totalTerbayar, $efektif['terbayar']);
            if ($tagihan->status !== 'bebas') {
                $totalTunggakan = $this->tambah($totalTunggakan, $efektif['sisa']);
            }
        }

        return [
            'total_tagihan' => $totalTagihan,
            'total_terbayar' => $totalTerbayar,
            'total_tunggakan' => $totalTunggakan,
        ];
    }

    /**
     * @return list<array{keluarga_id: int, nama_kepala: string, bulan: array<int, string|null>}>
     */
    public function gridBulanan(int $tenantId, int $tahun, int $iuranJenisId): array
    {
        $keluargaList = Keluarga::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'aktif')
            ->with(['warga' => fn ($q) => $q->where('hubungan', 'kepala')->where('status', 'aktif')])
            ->orderBy('alamat')
            ->get();

        $tagihanPerKeluarga = IuranTagihan::query()
            ->where('tenant_id', $tenantId)
            ->where('iuran_jenis_id', $iuranJenisId)
            ->where('periode', 'like', $tahun.'-%')
            ->get()
            ->groupBy('keluarga_id');

        $baris = [];

        foreach ($keluargaList as $keluarga) {
            $bulan = [];
            for ($m = 1; $m <= 12; $m++) {
                $bulan[$m] = null;
            }

            $tagihanKeluarga = $tagihanPerKeluarga->get($keluarga->id, collect());
            foreach ($tagihanKeluarga as $tagihan) {
                $parts = explode('-', $tagihan->periode);
                if (count($parts) === 2 && (int) $parts[0] === $tahun) {
                    $bulan[(int) $parts[1]] = $tagihan->status;
                }
            }

            $kepala = $keluarga->warga->first();

            $baris[] = [
                'keluarga_id' => $keluarga->id,
                'nama_kepala' => $kepala?->nama ?? '—',
                'bulan' => $bulan,
            ];
        }

        return $baris;
    }

    /**
     * @return list<array{keluarga_id: int, nama_kepala: string, alamat: string, jumlah_periode: int, total_tunggakan: string}>
     */
    public function daftarTunggakanKeluarga(int $tenantId, string $periodeHingga): array
    {
        $tagihan = IuranTagihan::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['belum', 'sebagian'])
            ->where('periode', '<=', $periodeHingga)
            ->with(['keluarga.warga' => fn ($q) => $q->where('hubungan', 'kepala')->where('status', 'aktif')])
            ->get();

        $perKeluarga = [];

        foreach ($tagihan as $row) {
            $efektif = $this->tagihanEfektif($row);
            if (bccomp($efektif['sisa'], '0', 2) <= 0) {
                continue;
            }

            $kid = $row->keluarga_id;
            if (! isset($perKeluarga[$kid])) {
                $kepala = $row->keluarga?->warga->first();
                $perKeluarga[$kid] = [
                    'keluarga_id' => $kid,
                    'nama_kepala' => $kepala?->nama ?? '—',
                    'alamat' => $row->keluarga?->alamat ?? '—',
                    'jumlah_periode' => 0,
                    'total_tunggakan' => '0.00',
                ];
            }

            $perKeluarga[$kid]['jumlah_periode']++;
            $perKeluarga[$kid]['total_tunggakan'] = $this->tambah(
                $perKeluarga[$kid]['total_tunggakan'],
                $efektif['sisa']
            );
        }

        $hasil = array_values($perKeluarga);

        usort($hasil, function (array $a, array $b): int {
            return bccomp($b['total_tunggakan'], $a['total_tunggakan'], 2);
        });

        return $hasil;
    }

    public function bebaskanTagihan(IuranTagihan $tagihan, string $alasan): void
    {
        $tagihan->status = 'bebas';
        $tagihan->alasan_bebas = $alasan;
        $tagihan->save();
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
