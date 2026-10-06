<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDokumenWargaRequest;
use App\Models\DokumenWarga;
use App\Models\Keluarga;
use App\Models\Warga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenWargaController extends Controller
{
    public function store(StoreDokumenWargaRequest $request, Keluarga $keluarga): RedirectResponse
    {
        $wargaId = $request->input('warga_id');
        if ($wargaId !== null && $wargaId !== '') {
            Warga::query()
                ->where('keluarga_id', $keluarga->id)
                ->findOrFail($wargaId);
        } else {
            $wargaId = null;
        }

        $file = $request->file('berkas');
        $path = $file->store('dokumen-warga', 'local');

        DokumenWarga::query()->create([
            'keluarga_id' => $keluarga->id,
            'warga_id' => $wargaId,
            'jenis' => $request->string('jenis')->toString(),
            'nama_asli' => $file->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('warga.show', $keluarga)
            ->with('status', 'Dokumen berhasil diunggah.');
    }

    public function destroy(DokumenWarga $dokumen): RedirectResponse
    {
        $keluargaId = $dokumen->keluarga_id;
        Storage::disk('local')->delete($dokumen->file_path);
        $dokumen->delete();

        return redirect()
            ->route('warga.show', $keluargaId)
            ->with('status', 'Dokumen berhasil dihapus.');
    }

    public function berkas(DokumenWarga $dokumen): StreamedResponse
    {
        if (! Storage::disk('local')->exists($dokumen->file_path)) {
            abort(404);
        }

        $user = request()->user();
        activity()
            ->causedBy($user)
            ->performedOn($dokumen)
            ->event('dokumen_download')
            ->withProperties(['nama_asli' => $dokumen->nama_asli])
            ->log('Unduh dokumen: '.$dokumen->nama_asli);

        return Storage::disk('local')->download($dokumen->file_path, $dokumen->nama_asli);
    }
}
