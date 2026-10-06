<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\ActiveRt;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboardService): View|RedirectResponse
    {
        $rt = ActiveRt::current();

        if ($rt === null) {
            return redirect()->route('rt-aktif.index');
        }

        return view('dashboard', [
            'rtNama' => $rt->nama,
            'ringkasan' => $dashboardService->ringkasan($rt->id),
        ]);
    }
}
