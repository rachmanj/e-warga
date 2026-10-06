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
                'url' => '#',
                'icon' => 'bi bi-people',
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
                'url' => '#',
                'icon' => 'bi bi-envelope-paper',
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
