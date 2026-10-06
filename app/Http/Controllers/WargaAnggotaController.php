<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWargaAnggotaRequest;
use App\Http\Requests\UpdateWargaAnggotaRequest;
use App\Models\Keluarga;
use App\Models\Warga;
use Illuminate\Http\RedirectResponse;

class WargaAnggotaController extends Controller
{
    public function store(StoreWargaAnggotaRequest $request, Keluarga $keluarga): RedirectResponse
    {
        Warga::query()->create([
            'keluarga_id' => $keluarga->id,
            ...$request->safe()->only([
                'nama',
                'nik',
                'hubungan',
                'jenis_kelamin',
                'tanggal_lahir',
                'pekerjaan',
                'agama',
                'status_perkawinan',
                'no_hp',
                'catatan',
            ]),
            'status' => $request->input('status', 'aktif'),
        ]);

        return redirect()
            ->route('warga.show', $keluarga)
            ->with('status', 'Anggota keluarga berhasil ditambahkan.');
    }

    public function update(UpdateWargaAnggotaRequest $request, Warga $warga): RedirectResponse
    {
        $data = $request->validated();
        if (! $request->filled('nik')) {
            unset($data['nik']);
        }
        $warga->update($data);

        return redirect()
            ->route('warga.show', $warga->keluarga_id)
            ->with('status', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Warga $warga): RedirectResponse
    {
        $keluargaId = $warga->keluarga_id;
        $warga->delete();

        return redirect()
            ->route('warga.show', $keluargaId)
            ->with('status', 'Anggota keluarga berhasil dihapus.');
    }
}
