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
                'url' => '#',
                'icon' => 'bi bi-cash-coin',
            ],
            [
                'text' => 'Kas',
                'url' => '#',
                'icon' => 'bi bi-wallet2',
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
