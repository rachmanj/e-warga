<?php

namespace App\Providers;

use App\Models\DokumenWarga;
use App\Models\Surat;
use Illuminate\Support\Facades\Route;
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
        Route::bind('dokumen', fn (string $value): DokumenWarga => DokumenWarga::query()->findOrFail($value));
        Route::bind('surat', fn (string $value): Surat => Surat::query()->findOrFail($value));
    }
}
