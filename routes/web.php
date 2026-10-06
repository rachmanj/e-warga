<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\RtAktifController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('rt-aktif', [RtAktifController::class, 'index'])->name('rt-aktif.index');
    Route::post('rt-aktif', [RtAktifController::class, 'store'])->name('rt-aktif.store');

    Route::middleware('rt.aktif')->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('ubah-sandi', [ChangePasswordController::class, 'edit'])->name('ubah-sandi.edit');
        Route::post('ubah-sandi', [ChangePasswordController::class, 'update'])->name('ubah-sandi.update');

        Route::get('pengguna', [PenggunaController::class, 'index'])
            ->middleware('permission:kelola_pengguna')
            ->name('pengguna.index');

        Route::get('pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    });
});
