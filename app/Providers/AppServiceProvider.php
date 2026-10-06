<?php

namespace App\Providers;

use App\Models\DokumenWarga;
use App\Models\IuranJenis;
use App\Models\IuranPembayaran;
use App\Models\IuranTagihan;
use App\Models\KasTransaksi;
use App\Models\Surat;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' && str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        Route::bind('dokumen', fn (string $value): DokumenWarga => DokumenWarga::query()->findOrFail($value));
        Route::bind('surat', fn (string $value): Surat => Surat::query()->findOrFail($value));
        Route::bind('jenis', fn (string $value): IuranJenis => IuranJenis::query()->findOrFail($value));
        Route::bind('tagihan', fn (string $value): IuranTagihan => IuranTagihan::query()->findOrFail($value));
        Route::bind('pembayaran', fn (string $value): IuranPembayaran => IuranPembayaran::query()->findOrFail($value));
        Route::bind('kas', fn (string $value): KasTransaksi => KasTransaksi::query()->findOrFail($value));
    }
}
