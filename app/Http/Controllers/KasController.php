<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKasSaldoAwalRequest;
use App\Http\Requests\StoreKasTransaksiRequest;
use App\Models\KasKategori;
use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use App\Services\KasService;
use App\Support\ActiveRt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KasController extends Controller
{
    public function __construct(
        private KasService $kasService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('lihat_kas'), 403);

        $rt = ActiveRt::current();
        $tenantId = $rt?->id ?? 0;
        $tahun = (int) $request->input('tahun', (int) date('Y'));
        $pos = $request->input('pos', 'tunai');
        if (! in_array($pos, ['tunai', 'bank'], true)) {
            $pos = 'tunai';
        }

        $buku = $this->kasService->bukuKas($tenantId, $tahun, $pos);
        $kategori = KasKategori::query()->orderBy('nama')->get();

        return view('kas.index', [
            'tahun' => $tahun,
            'pos' => $pos,
            'buku' => $buku,
            'kategori' => $kategori,
        ]);
    }

    public function rekap(Request $request): View
    {
        abort_unless($request->user()?->can('lihat_kas'), 403);

        $rt = ActiveRt::current();
        $tenantId = $rt?->id ?? 0;
        $tahun = (int) $request->input('tahun', (int) date('Y'));
        $pos = $request->input('pos', 'tunai');
        if (! in_array($pos, ['tunai', 'bank'], true)) {
            $pos = 'tunai';
        }

        $rekap = $this->kasService->rekapBulanan($tenantId, $tahun, $pos);

        return view('kas.rekap', [
            'tahun' => $tahun,
            'pos' => $pos,
            'rekap' => $rekap,
        ]);
    }

    public function saldoAwalStore(StoreKasSaldoAwalRequest $request): RedirectResponse
    {
        $rt = ActiveRt::current();

        KasSaldoAwal::query()->updateOrCreate(
            [
                'tenant_id' => $rt?->id,
                'tahun' => $request->integer('tahun'),
                'pos' => $request->string('pos')->toString(),
            ],
            [
                'jumlah' => number_format((float) $request->input('jumlah'), 2, '.', ''),
            ]
        );

        return redirect()
            ->route('kas.index', [
                'tahun' => $request->integer('tahun'),
                'pos' => $request->input('pos'),
            ])
            ->with('status', 'Saldo awal berhasil disimpan.');
    }

    public function transaksiStore(StoreKasTransaksiRequest $request): RedirectResponse
    {
        KasTransaksi::query()->create([
            'tanggal' => $request->string('tanggal')->toString(),
            'jenis' => $request->string('jenis')->toString(),
            'pos' => $request->string('pos')->toString(),
            'kas_kategori_id' => $request->input('kas_kategori_id'),
            'uraian' => $request->string('uraian')->toString(),
            'jumlah' => number_format((float) $request->input('jumlah'), 2, '.', ''),
            'no_bukti' => $request->input('no_bukti'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('kas.index', [
                'tahun' => (int) date('Y', strtotime($request->string('tanggal')->toString())),
                'pos' => $request->input('pos'),
            ])
            ->with('status', 'Transaksi kas berhasil ditambahkan.');
    }

    public function transaksiDestroy(Request $request, KasTransaksi $kas): RedirectResponse
    {
        abort_unless($request->user()?->can('kelola_kas'), 403);

        if ($kas->iuran_pembayaran_id !== null) {
            return redirect()
                ->back()
                ->with('status', 'Transaksi kas yang berasal dari pembayaran iuran tidak dapat dihapus.');
        }

        $uraian = $kas->uraian;
        $kas->delete();

        return redirect()
            ->back()
            ->with('status', "Transaksi \"{$uraian}\" berhasil dihapus.");
    }
}
