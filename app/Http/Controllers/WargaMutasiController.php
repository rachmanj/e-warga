<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWargaMutasiRequest;
use App\Models\Keluarga;
use App\Models\Warga;
use App\Models\WargaMutasi;
use Illuminate\Http\RedirectResponse;

class WargaMutasiController extends Controller
{
    public function store(StoreWargaMutasiRequest $request, Keluarga $keluarga): RedirectResponse
    {
        $wargaId = $request->input('warga_id');
        if ($wargaId !== null && $wargaId !== '') {
            Warga::query()
                ->where('keluarga_id', $keluarga->id)
                ->findOrFail($wargaId);
        } else {
            $wargaId = null;
        }

        WargaMutasi::query()->create([
            'keluarga_id' => $keluarga->id,
            'warga_id' => $wargaId,
            'jenis' => $request->string('jenis')->toString(),
            'tanggal' => $request->input('tanggal'),
            'keterangan' => $request->input('keterangan'),
            'dicatat_oleh' => $request->user()?->id,
        ]);

        return redirect()
            ->route('warga.show', $keluarga)
            ->with('status', 'Mutasi berhasil dicatat.');
    }
}
