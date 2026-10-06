@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $dashboard_url = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'dashboard');
    $dashboard_url = $layoutHelper->makeUrl($dashboard_url);
    $logoText = config('adminlte.logo', '<b>e</b>-Warga');
    $brandClass = config('adminlte.classes_brand', '');
    $brandTextClass = config('adminlte.classes_brand_text', 'fw-light');
@endphp

@if($layoutHelper->isLayoutTopnavEnabled())
    <a href="{{ $dashboard_url }}" class="navbar-brand d-flex align-items-center {{ $brandClass }}">
        <span class="{{ $brandTextClass }}">{!! $logoText !!}</span>
    </a>
@else
    <div class="sidebar-brand">
        <a href="{{ $dashboard_url }}" class="brand-link {{ $brandClass }}">
            <span class="brand-text {{ $brandTextClass }}">{!! $logoText !!}</span>
        </a>
    </div>
@endif
