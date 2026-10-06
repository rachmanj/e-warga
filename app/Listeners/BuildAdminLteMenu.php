<?php

namespace App\Listeners;

use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class BuildAdminLteMenu
{
    public function handle(BuildingMenu $event): void
    {
        $event->menu->add(
            [
                'text' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'bi bi-speedometer2',
            ],
            [
                'text' => 'Warga',
                'route' => 'warga.index',
                'icon' => 'bi bi-people',
                'can' => 'lihat_warga',
            ],
            [
                'text' => 'Iuran',
                'icon' => 'bi bi-cash-coin',
                'can' => 'lihat_iuran',
                'submenu' => [
                    [
                        'text' => 'Daftar Iuran',
                        'route' => 'iuran.index',
                        'icon' => 'bi bi-grid-3x3',
                        'can' => 'lihat_iuran',
                    ],
                    [
                        'text' => 'Tagihan',
                        'route' => 'iuran.tagihan.index',
                        'icon' => 'bi bi-receipt',
                        'can' => 'lihat_iuran',
                    ],
                    [
                        'text' => 'Tunggakan',
                        'route' => 'iuran.tunggakan',
                        'icon' => 'bi bi-exclamation-triangle',
                        'can' => 'lihat_tunggakan',
                    ],
                    [
                        'text' => 'Jenis Iuran',
                        'route' => 'iuran.jenis.index',
                        'icon' => 'bi bi-tags',
                        'can' => 'kelola_iuran',
                    ],
                ],
            ],
            [
                'text' => 'Kas',
                'icon' => 'bi bi-wallet2',
                'can' => 'lihat_kas',
                'submenu' => [
                    [
                        'text' => 'Buku Kas',
                        'route' => 'kas.index',
                        'icon' => 'bi bi-journal-text',
                        'can' => 'lihat_kas',
                    ],
                    [
                        'text' => 'Rekap Bulanan',
                        'route' => 'kas.rekap',
                        'icon' => 'bi bi-bar-chart',
                        'can' => 'lihat_kas',
                    ],
                ],
            ],
            [
                'text' => 'Surat',
                'route' => 'surat.index',
                'icon' => 'bi bi-envelope-paper',
                'can' => 'lihat_surat',
            ],
            [
                'text' => 'Pengguna',
                'route' => 'pengguna.index',
                'icon' => 'bi bi-person-gear',
                'can' => 'kelola_pengguna',
            ],
            [
                'text' => 'Pengaturan',
                'route' => 'pengaturan.index',
                'icon' => 'bi bi-gear',
            ],
        );
    }
}
