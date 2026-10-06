<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use Illuminate\View\View;

class VerifikasiSuratController extends Controller
{
    public function show(string $kode): View
    {
        $surat = Surat::query()
            ->withoutGlobalScope('tenant')
            ->with(['jenis', 'warga'])
            ->where('kode_verifikasi', $kode)
            ->where('status', 'terbit')
            ->first();

        if ($surat === null) {
            abort(404);
        }

        return view('verifikasi.show', [
            'surat' => $surat,
        ]);
    }
}
