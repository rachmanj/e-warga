<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(): View
    {
        return view('pengguna.index');
    }
}
