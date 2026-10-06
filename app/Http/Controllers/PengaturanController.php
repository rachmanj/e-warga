<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function index(): View
    {
        return view('pengaturan.index');
    }
}
