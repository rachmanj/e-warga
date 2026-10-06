<?php

namespace App\Http\Controllers;

use App\Models\Rt;
use App\Support\ActiveRt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RtAktifController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = request()->user();

        if (! $user?->isSuperAdmin()) {
            abort(403);
        }

        $rts = Rt::query()->orderBy('nama')->get();

        return view('rt-aktif.index', [
            'rts' => $rts,
            'rtAktifId' => session(ActiveRt::SESSION_KEY),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'rt_id' => ['required', 'exists:rts,id'],
        ]);

        $rt = Rt::query()->findOrFail($validated['rt_id']);

        if (! $rt->publik_aktif) {
            return back()->withErrors(['rt_id' => 'RT yang dipilih tidak aktif.']);
        }

        ActiveRt::setForSuperAdmin((int) $rt->id);

        return redirect()->route('dashboard');
    }
}
