<?php

namespace App\Http\Controllers;

use App\Support\ActiveRt;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $rt = ActiveRt::current();

        return view('dashboard', [
            'rtNama' => $rt?->nama ?? '—',
            'totalKk' => 0,
            'totalJiwa' => 0,
            'tunggakanBulanIni' => 0,
            'saldoKas' => 0,
        ]);
    }
}
