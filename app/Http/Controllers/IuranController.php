<?php

namespace App\Http\Controllers;

use App\Http\Requests\BebasIuranTagihanRequest;
use App\Http\Requests\StoreIuranJenisRequest;
use App\Http\Requests\StoreIuranPembayaranRequest;
use App\Http\Requests\StoreIuranTagihanRequest;
use App\Http\Requests\StoreIuranTarifRequest;
use App\Http\Requests\UpdateIuranJenisRequest;
use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\IuranTarif;
use App\Models\Keluarga;
use App\Services\IuranService;
use App\Support\ActiveRt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class IuranController extends Controller
{
    public function __construct(
        private IuranService $iuranService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('lihat_iuran'), 403);

        $rt = ActiveRt::current();
        $tenantId = $rt?->id ?? 0;
        $tahun = (int) $request->input('tahun', (int) date('Y'));

        $jenisList = IuranJenis::query()->where('aktif', true)->orderBy('nama')->get();
        $jenisId = $request->filled('jenis')
            ? (int) $request->input('jenis')
            : ($jenisList->first()?->id);

        $grid = $jenisId !== null
            ? $this->iuranService->gridBulanan($tenantId, $tahun, $jenisId)
            : [];

        $ringkasan = $this->iuranService->ringkasanTahun($tenantId, $tahun, $jenisId);

        return view('iuran.index', [
            'tahun' => $tahun,
            'jenisId' => $jenisId,
            'jenisList' => $jenisList,
            'grid' => $grid,
            'ringkasan' => $ringkasan,
        ]);
    }

    public function tagihanIndex(Request $request): View
    {
        abort_unless($request->user()?->can('lihat_iuran'), 403);

        $query = IuranTagihan::query()
            ->with(['keluarga.warga', 'jenis'])
            ->orderByDesc('periode')
            ->orderBy('keluarga_id');

        if ($request->filled('periode')) {
            $query->where('periode', $request->string('periode')->toString());
        }

        if ($request->filled('iuran_jenis_id')) {
            $query->where('iuran_jenis_id', $request->integer('iuran_jenis_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $tagihan = $query->paginate(20)->withQueryString();
        $jenisList = IuranJenis::query()->orderBy('nama')->get();

        $efektifPerId = [];
        foreach ($tagihan as $row) {
            $efektifPerId[$row->id] = $this->iuranService->tagihanEfektif($row);
        }

        return view('iuran.tagihan.index', [
            'tagihan' => $tagihan,
            'jenisList' => $jenisList,
            'efektifPerId' => $efektifPerId,
            'filters' => $request->only(['periode', 'iuran_jenis_id', 'status']),
        ]);
    }

    public function tagihanShow(Request $request, IuranTagihan $tagihan): View
    {
        abort_unless($request->user()?->can('lihat_iuran'), 403);

        $tagihan->load(['keluarga.warga', 'jenis', 'pembayaran.dicatatOleh', 'pembayaran.kwitansi']);
        $efektif = $this->iuranService->tagihanEfektif($tagihan);

        return view('iuran.tagihan.show', [
            'tagihan' => $tagihan,
            'efektif' => $efektif,
        ]);
    }

    public function tunggakan(Request $request): View
    {
        abort_unless($request->user()?->can('lihat_tunggakan'), 403);

        $rt = ActiveRt::current();
        $periodeHingga = now()->format('Y-m');
        $daftar = $this->iuranService->daftarTunggakanKeluarga($rt?->id ?? 0, $periodeHingga);

        return view('iuran.tunggakan', [
            'daftar' => $daftar,
            'periodeHingga' => $periodeHingga,
        ]);
    }

    public function jenisIndex(Request $request): View
    {
        abort_unless($request->user()?->can('kelola_iuran'), 403);

        $jenisList = IuranJenis::query()->withCount('tagihan')->orderBy('nama')->get();
        $keluarga = Keluarga::query()
            ->where('status', 'aktif')
            ->with(['warga' => fn ($q) => $q->where('hubungan', 'kepala')])
            ->orderBy('alamat')
            ->get();

        return view('iuran.jenis.index', [
            'jenisList' => $jenisList,
            'keluarga' => $keluarga,
        ]);
    }

    public function jenisStore(StoreIuranJenisRequest $request): RedirectResponse
    {
        IuranJenis::query()->create([
            'nama' => $request->string('nama')->toString(),
            'nominal_default' => $request->input('nominal_default'),
            'periode' => $request->string('periode')->toString(),
            'aktif' => $request->boolean('aktif', true),
            'keterangan' => $request->input('keterangan'),
        ]);

        return redirect()->route('iuran.jenis.index')->with('status', 'Jenis iuran berhasil ditambahkan.');
    }

    public function jenisUpdate(UpdateIuranJenisRequest $request, IuranJenis $jenis): RedirectResponse
    {
        $jenis->update([
            'nama' => $request->string('nama')->toString(),
            'nominal_default' => $request->input('nominal_default'),
            'periode' => $request->string('periode')->toString(),
            'aktif' => $request->boolean('aktif', true),
            'keterangan' => $request->input('keterangan'),
        ]);

        return redirect()->route('iuran.jenis.index')->with('status', 'Jenis iuran berhasil diperbarui.');
    }

    public function jenisDestroy(Request $request, IuranJenis $jenis): RedirectResponse
    {
        abort_unless($request->user()?->can('kelola_iuran'), 403);

        if ($jenis->tagihan()->exists()) {
            return redirect()
                ->route('iuran.jenis.index')
                ->with('status', 'Jenis iuran tidak dapat dihapus karena sudah memiliki tagihan.');
        }

        $nama = $jenis->nama;
        $jenis->delete();

        return redirect()->route('iuran.jenis.index')->with('status', "Jenis iuran \"{$nama}\" berhasil dihapus.");
    }

    public function jenisTarif(StoreIuranTarifRequest $request, IuranJenis $jenis): RedirectResponse
    {
        IuranTarif::query()->updateOrCreate(
            [
                'tenant_id' => $jenis->tenant_id,
                'iuran_jenis_id' => $jenis->id,
                'keluarga_id' => $request->integer('keluarga_id'),
            ],
            [
                'nominal' => number_format((float) $request->input('nominal'), 2, '.', ''),
            ]
        );

        return redirect()->route('iuran.jenis.index')->with('status', 'Tarif khusus keluarga berhasil disimpan.');
    }

    public function tagihanStore(StoreIuranTagihanRequest $request): RedirectResponse
    {
        $jenis = IuranJenis::query()->findOrFail($request->integer('iuran_jenis_id'));
        $hasil = $this->iuranService->buatTagihan(
            $jenis,
            $request->string('periode')->toString(),
            $request->input('jatuh_tempo')
        );

        return redirect()
            ->route('iuran.tagihan.index', ['periode' => $request->input('periode')])
            ->with('status', "Tagihan dibuat: {$hasil['dibuat']}, dilewati: {$hasil['dilewati']}.");
    }

    public function tagihanBebas(BebasIuranTagihanRequest $request, IuranTagihan $tagihan): RedirectResponse
    {
        $this->iuranService->bebaskanTagihan($tagihan, $request->string('alasan')->toString());

        return redirect()
            ->route('iuran.tagihan.show', $tagihan)
            ->with('status', 'Tagihan dibebaskan.');
    }

    public function pembayaranStore(StoreIuranPembayaranRequest $request, IuranTagihan $tagihan): RedirectResponse
    {
        $opsi = [
            'no_referensi' => $request->input('no_referensi'),
            'bukti_path' => null,
        ];

        if ($request->hasFile('bukti')) {
            $path = $request->file('bukti')->store('bukti-pembayaran', 'local');
            $opsi['bukti_path'] = $path;
        }

        $jumlah = number_format((float) $request->input('jumlah'), 2, '.', '');

        $this->iuranService->catatPembayaran(
            $tagihan,
            $request->string('tanggal')->toString(),
            $jumlah,
            $request->string('metode')->toString(),
            (int) $request->user()->id,
            $opsi
        );

        return redirect()
            ->route('iuran.tagihan.show', $tagihan)
            ->with('status', 'Pembayaran berhasil dicatat.');
    }

    public function pembayaranDestroy(Request $request, IuranPembayaran $pembayaran): RedirectResponse
    {
        abort_unless($request->user()?->can('kelola_iuran'), 403);

        $tagihan = $pembayaran->tagihan;
        $this->iuranService->hapusPembayaran($pembayaran);

        return redirect()
            ->route('iuran.tagihan.show', $tagihan)
            ->with('status', 'Pembayaran berhasil dihapus.');
    }

    public function kwitansi(Request $request, IuranPembayaran $pembayaran): Response
    {
        abort_unless($request->user()?->can('lihat_iuran'), 403);

        $pembayaran->load(['kwitansi', 'tagihan.jenis', 'tagihan.keluarga.warga']);
        $rt = ActiveRt::current();
        $kwitansi = $pembayaran->kwitansi;
        $tagihan = $pembayaran->tagihan;
        $kepala = $tagihan->keluarga?->warga->firstWhere('hubungan', 'kepala');

        $pdf = Pdf::loadView('iuran.kwitansi-pdf', [
            'rt' => $rt,
            'kwitansi' => $kwitansi,
            'pembayaran' => $pembayaran,
            'tagihan' => $tagihan,
            'kepala' => $kepala,
        ]);

        $filename = 'kwitansi-'.($kwitansi?->nomor ?? $pembayaran->id).'.pdf';

        return $pdf->download($filename);
    }
}
