<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeluargaRequest;
use App\Http\Requests\UpdateKeluargaRequest;
use App\Models\Keluarga;
use App\Models\Warga;
use App\Models\WargaMutasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WargaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Keluarga::query()
            ->withCount('warga')
            ->with(['warga' => fn ($q) => $q->where('hubungan', 'kepala')]);

        if ($request->filled('status_hunian')) {
            $query->where('status_hunian', $request->string('status_hunian')->toString());
        }

        if ($request->filled('blok_unit')) {
            $query->where('blok_unit', $request->string('blok_unit')->toString());
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('alamat', 'like', $term)
                    ->orWhereHas('warga', fn ($w) => $w->where('nama', 'like', $term));
            });
        }

        $keluarga = $query->orderBy('alamat')->paginate(15)->withQueryString();

        return view('warga.index', [
            'keluarga' => $keluarga,
            'filters' => $request->only(['status_hunian', 'blok_unit', 'q']),
            'canKelola' => $request->user()?->can('kelola_warga') ?? false,
        ]);
    }

    public function create(): View
    {
        return view('warga.create');
    }

    public function store(StoreKeluargaRequest $request): RedirectResponse
    {
        $keluarga = Keluarga::query()->create($request->safe()->only([
            'no_kk',
            'alamat',
            'blok_unit',
            'rt_lingkungan',
            'status_hunian',
            'nama_pemilik',
            'tanggal_masuk',
            'tanggal_keluar',
            'status',
            'keterangan',
        ]));

        Warga::query()->create([
            'keluarga_id' => $keluarga->id,
            'nama' => $request->string('nama')->toString(),
            'nik' => $request->input('nik'),
            'hubungan' => 'kepala',
            'jenis_kelamin' => $request->string('jenis_kelamin')->toString(),
            'tanggal_lahir' => $request->input('tanggal_lahir'),
            'pekerjaan' => $request->input('pekerjaan'),
            'agama' => $request->input('agama'),
            'status_perkawinan' => $request->input('status_perkawinan'),
            'no_hp' => $request->input('no_hp'),
            'status' => 'aktif',
        ]);

        WargaMutasi::query()->create([
            'keluarga_id' => $keluarga->id,
            'jenis' => 'masuk',
            'tanggal' => $request->input('tanggal_masuk') ?? now()->toDateString(),
            'keterangan' => 'Pendaftaran keluarga baru',
            'dicatat_oleh' => $request->user()?->id,
        ]);

        return redirect()
            ->route('warga.show', $keluarga)
            ->with('status', 'Keluarga berhasil ditambahkan.');
    }

    public function show(Keluarga $keluarga): View
    {
        $keluarga->load([
            'warga' => fn ($q) => $q->orderByRaw("CASE hubungan WHEN 'kepala' THEN 0 WHEN 'istri' THEN 1 WHEN 'anak' THEN 2 ELSE 3 END")->orderBy('nama'),
            'mutasi' => fn ($q) => $q->with(['warga', 'dicatatOleh'])->latest('tanggal')->latest('id'),
            'dokumen' => fn ($q) => $q->with('warga')->latest(),
        ]);

        return view('warga.show', [
            'keluarga' => $keluarga,
            'canKelola' => request()->user()?->can('kelola_warga') ?? false,
            'canKelolaDokumen' => request()->user()?->can('kelola_dokumen') ?? false,
            'canLihatDokumen' => request()->user()?->can('lihat_dokumen') ?? false,
        ]);
    }

    public function edit(Keluarga $keluarga): View
    {
        return view('warga.edit', ['keluarga' => $keluarga]);
    }

    public function update(UpdateKeluargaRequest $request, Keluarga $keluarga): RedirectResponse
    {
        $keluarga->update($request->safe()->only([
            'no_kk',
            'alamat',
            'blok_unit',
            'rt_lingkungan',
            'status_hunian',
            'nama_pemilik',
            'tanggal_masuk',
            'tanggal_keluar',
            'status',
            'keterangan',
        ]));

        return redirect()
            ->route('warga.show', $keluarga)
            ->with('status', 'Data keluarga berhasil diperbarui.');
    }

    public function destroy(Keluarga $keluarga): RedirectResponse
    {
        $keluarga->delete();

        return redirect()
            ->route('warga.index')
            ->with('status', 'Keluarga berhasil dihapus.');
    }
}
