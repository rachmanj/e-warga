<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenWargaController;
use App\Http\Controllers\IuranController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\RtAktifController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\VerifikasiSuratController;
use App\Http\Controllers\WargaAnggotaController;
use App\Http\Controllers\WargaController;
use App\Http\Controllers\WargaMutasiController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('verifikasi/{kode}', [VerifikasiSuratController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('verifikasi.show');

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

        Route::middleware('permission:kelola_warga')->group(function (): void {
            Route::get('warga/create', [WargaController::class, 'create'])->name('warga.create');
            Route::post('warga', [WargaController::class, 'store'])->name('warga.store');
            Route::get('warga/{keluarga}/edit', [WargaController::class, 'edit'])->name('warga.edit');
            Route::put('warga/{keluarga}', [WargaController::class, 'update'])->name('warga.update');
            Route::delete('warga/{keluarga}', [WargaController::class, 'destroy'])->name('warga.destroy');

            Route::post('warga/{keluarga}/anggota', [WargaAnggotaController::class, 'store'])->name('warga.anggota.store');
            Route::put('warga/anggota/{warga}', [WargaAnggotaController::class, 'update'])->name('warga.anggota.update');
            Route::delete('warga/anggota/{warga}', [WargaAnggotaController::class, 'destroy'])->name('warga.anggota.destroy');

            Route::post('warga/{keluarga}/mutasi', [WargaMutasiController::class, 'store'])->name('warga.mutasi.store');
        });

        Route::middleware('permission:lihat_warga')->group(function (): void {
            Route::get('warga', [WargaController::class, 'index'])->name('warga.index');
            Route::get('warga/{keluarga}', [WargaController::class, 'show'])->name('warga.show');
        });

        Route::middleware('permission:kelola_dokumen')->group(function (): void {
            Route::post('warga/{keluarga}/dokumen', [DokumenWargaController::class, 'store'])->name('warga.dokumen.store');
            Route::delete('dokumen/{dokumen}', [DokumenWargaController::class, 'destroy'])->name('dokumen.destroy');
        });

        Route::get('dokumen/{dokumen}/berkas', [DokumenWargaController::class, 'berkas'])
            ->middleware('permission:lihat_dokumen')
            ->name('dokumen.berkas');

        Route::middleware('permission:kelola_surat')->group(function (): void {
            Route::get('surat/create', [SuratController::class, 'create'])->name('surat.create');
            Route::post('surat', [SuratController::class, 'store'])->name('surat.store');
            Route::post('surat/{surat}/setujui', [SuratController::class, 'setujui'])->name('surat.setujui');
            Route::post('surat/{surat}/tolak', [SuratController::class, 'tolak'])->name('surat.tolak');
            Route::post('surat/{surat}/catatan', [SuratController::class, 'catatan'])->name('surat.catatan');
        });

        Route::middleware('permission:lihat_iuran')->group(function (): void {
            Route::get('iuran', [IuranController::class, 'index'])->name('iuran.index');
            Route::get('iuran/tagihan', [IuranController::class, 'tagihanIndex'])->name('iuran.tagihan.index');
            Route::get('iuran/tagihan/{tagihan}', [IuranController::class, 'tagihanShow'])->name('iuran.tagihan.show');
            Route::get('iuran/pembayaran/{pembayaran}/kwitansi', [IuranController::class, 'kwitansi'])->name('iuran.pembayaran.kwitansi');
        });

        Route::get('iuran/tunggakan', [IuranController::class, 'tunggakan'])
            ->middleware('permission:lihat_tunggakan')
            ->name('iuran.tunggakan');

        Route::middleware('permission:kelola_iuran')->group(function (): void {
            Route::get('iuran/jenis', [IuranController::class, 'jenisIndex'])->name('iuran.jenis.index');
            Route::post('iuran/jenis', [IuranController::class, 'jenisStore'])->name('iuran.jenis.store');
            Route::put('iuran/jenis/{jenis}', [IuranController::class, 'jenisUpdate'])->name('iuran.jenis.update');
            Route::delete('iuran/jenis/{jenis}', [IuranController::class, 'jenisDestroy'])->name('iuran.jenis.destroy');
            Route::post('iuran/jenis/{jenis}/tarif', [IuranController::class, 'jenisTarif'])->name('iuran.jenis.tarif');
            Route::post('iuran/tagihan', [IuranController::class, 'tagihanStore'])->name('iuran.tagihan.store');
            Route::post('iuran/tagihan/{tagihan}/bebas', [IuranController::class, 'tagihanBebas'])->name('iuran.tagihan.bebas');
            Route::post('iuran/tagihan/{tagihan}/pembayaran', [IuranController::class, 'pembayaranStore'])->name('iuran.tagihan.pembayaran');
            Route::delete('iuran/pembayaran/{pembayaran}', [IuranController::class, 'pembayaranDestroy'])->name('iuran.pembayaran.destroy');
        });

        Route::middleware('permission:lihat_kas')->group(function (): void {
            Route::get('kas', [KasController::class, 'index'])->name('kas.index');
            Route::get('kas/rekap', [KasController::class, 'rekap'])->name('kas.rekap');
        });

        Route::middleware('permission:kelola_kas')->group(function (): void {
            Route::post('kas/saldo-awal', [KasController::class, 'saldoAwalStore'])->name('kas.saldo-awal.store');
            Route::post('kas/transaksi', [KasController::class, 'transaksiStore'])->name('kas.transaksi.store');
            Route::delete('kas/transaksi/{kas}', [KasController::class, 'transaksiDestroy'])->name('kas.transaksi.destroy');
        });

        Route::middleware('permission:lihat_surat')->group(function (): void {
            Route::get('surat', [SuratController::class, 'index'])->name('surat.index');
            Route::get('surat/{surat}', [SuratController::class, 'show'])->name('surat.show');
            Route::get('surat/{surat}/pdf', [SuratController::class, 'pdf'])->name('surat.pdf');
        });

        Route::post('surat/{surat}/terbitkan', [SuratController::class, 'terbitkan'])
            ->middleware('permission:terbitkan_surat')
            ->name('surat.terbitkan');
    });
});
