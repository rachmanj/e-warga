<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengaturanRtRequest;
use App\Support\ActiveRt;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function index(): View
    {
        return view('pengaturan.index', [
            'rt' => ActiveRt::current(),
        ]);
    }

    public function updateRt(UpdatePengaturanRtRequest $request): RedirectResponse
    {
        $rt = ActiveRt::current();

        if ($rt === null) {
            abort(422, 'Tidak ada RT aktif. Pilih RT terlebih dahulu.');
        }

        $rt->fill($request->validated());
        $kolomDiubah = array_keys($rt->getDirty());

        if ($kolomDiubah !== []) {
            $rt->save();

            $user = $request->user();
            $namaPengguna = $user?->nama ?? $user?->name ?? '—';

            activity()
                ->causedBy($user)
                ->performedOn($rt)
                ->event('rt_identitas_diubah')
                ->withProperties([
                    'nama_pengguna' => $namaPengguna,
                    'kolom_diubah' => $kolomDiubah,
                ])
                ->log('Mengubah identitas RT: '.implode(', ', $kolomDiubah));
        }

        return redirect()
            ->route('pengaturan.index')
            ->with('status', 'Data RT berhasil disimpan.');
    }
}
