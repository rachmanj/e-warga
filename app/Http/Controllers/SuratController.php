<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuratCatatanRequest;
use App\Http\Requests\StoreSuratRequest;
use App\Http\Requests\TolakSuratRequest;
use App\Models\Keluarga;
use App\Models\Surat;
use App\Models\SuratCatatan;
use App\Models\SuratJenis;
use App\Services\SuratService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuratController extends Controller
{
    public function __construct(
        private SuratService $suratService,
    ) {}

    public function index(Request $request): View
    {
        $query = Surat::query()
            ->with(['jenis', 'warga', 'dibuatOleh'])
            ->orderByDesc('tanggal_ajuan')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('surat_jenis_id')) {
            $query->where('surat_jenis_id', $request->integer('surat_jenis_id'));
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', $request->integer('tahun'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->whereHas('warga', fn ($w) => $w->where('nama', 'like', $term));
        }

        $surat = $query->paginate(15)->withQueryString();
        $jenisList = SuratJenis::query()->where('aktif', true)->orderBy('urutan')->get();
        $tahunList = Surat::query()->select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');

        return view('surat.index', [
            'surat' => $surat,
            'jenisList' => $jenisList,
            'tahunList' => $tahunList,
            'filters' => $request->only(['status', 'surat_jenis_id', 'tahun', 'q']),
        ]);
    }

    public function create(): View
    {
        $jenisList = SuratJenis::query()->where('aktif', true)->orderBy('urutan')->get();
        $keluarga = Keluarga::query()
            ->with(['warga' => fn ($q) => $q->where('status', 'aktif')->orderBy('nama')])
            ->where('status', 'aktif')
            ->orderBy('alamat')
            ->get();

        return view('surat.create', [
            'jenisList' => $jenisList,
            'keluarga' => $keluarga,
            'anggotaPerKeluarga' => $keluarga->mapWithKeys(
                fn ($kk) => [$kk->id => $kk->warga->map(fn ($w) => ['id' => $w->id, 'nama' => $w->nama])->values()]
            ),
        ]);
    }

    public function store(StoreSuratRequest $request): RedirectResponse
    {
        $surat = Surat::query()->create([
            'surat_jenis_id' => $request->integer('surat_jenis_id'),
            'keluarga_id' => $request->input('keluarga_id'),
            'warga_id' => $request->input('warga_id'),
            'tahun' => (int) now()->format('Y'),
            'keperluan' => $request->string('keperluan')->toString(),
            'tanggal_ajuan' => now()->toDateString(),
            'status' => 'diajukan',
            'dibuat_oleh' => $request->user()->id,
            'kode_verifikasi' => $this->suratService->buatKodeVerifikasi(),
        ]);

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', 'Pengajuan surat berhasil disimpan.');
    }

    public function show(Surat $surat): View
    {
        $surat->load([
            'jenis',
            'warga',
            'keluarga',
            'dibuatOleh',
            'disetujuiOleh',
            'catatan' => fn ($q) => $q->with('pengguna')->orderByDesc('created_at'),
        ]);

        return view('surat.show', [
            'surat' => $surat,
        ]);
    }

    public function setujui(Request $request, Surat $surat): RedirectResponse
    {
        abort_unless($request->user()?->can('kelola_surat'), 403);

        if ($surat->status !== 'diajukan') {
            return redirect()
                ->route('surat.show', $surat)
                ->with('status', 'Surat tidak dapat disetujui pada status saat ini.');
        }

        $surat->update([
            'status' => 'disetujui',
            'disetujui_oleh' => $request->user()->id,
            'disetujui_at' => now(),
        ]);

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', 'Surat disetujui.');
    }

    public function tolak(TolakSuratRequest $request, Surat $surat): RedirectResponse
    {
        if ($surat->isTerbit()) {
            return redirect()
                ->route('surat.show', $surat)
                ->with('status', 'Surat yang sudah terbit tidak dapat ditolak.');
        }

        $this->suratService->tolak($surat, $request->string('alasan_tolak')->toString());

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', 'Surat ditolak.');
    }

    public function terbitkan(Request $request, Surat $surat): RedirectResponse
    {
        abort_unless($request->user()?->can('terbitkan_surat'), 403);

        if (! in_array($surat->status, ['diajukan', 'disetujui'], true)) {
            return redirect()
                ->route('surat.show', $surat)
                ->with('status', 'Surat tidak dapat diterbitkan pada status saat ini.');
        }

        $this->suratService->terbitkan($surat);

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', 'Surat berhasil diterbitkan.');
    }

    public function catatan(StoreSuratCatatanRequest $request, Surat $surat): RedirectResponse
    {
        SuratCatatan::query()->create([
            'surat_id' => $surat->id,
            'catatan' => $request->string('catatan')->toString(),
            'oleh' => $request->user()->id,
        ]);

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', 'Catatan ditambahkan.');
    }

    public function pdf(Request $request, Surat $surat): StreamedResponse
    {
        abort_unless($request->user()?->can('lihat_surat'), 403);

        if (! $surat->isTerbit() || $surat->file_path === null) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($surat->file_path)) {
            abort(404);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($surat)
            ->event('surat_download')
            ->withProperties(['nomor_lengkap' => $surat->nomor_lengkap])
            ->log('Unduh PDF surat: '.$surat->nomor_lengkap);

        $filename = 'surat-'.str_replace(['/', '\\'], '-', $surat->nomor_lengkap ?? (string) $surat->id).'.pdf';

        return Storage::disk('local')->download($surat->file_path, $filename);
    }
}
