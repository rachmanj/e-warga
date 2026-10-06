@php
    use App\Support\FormatUang;
    $t = $ringkasan['tunggakan_bulan_berjalan'];
@endphp

@extends('adminlte::page')

@section('title', 'Dasbor')

@section('content_header')
    <h1>Dasbor</h1>
@stop

@section('content')
    <p class="text-muted mb-3">RT aktif: <strong>{{ $rtNama }}</strong></p>

    <div class="d-flex flex-wrap gap-2 mb-4">
        @can('kelola_warga')
            <a href="{{ route('warga.create') }}" class="btn btn-sm text-white" style="background-color: #0f766e;">Tambah Warga</a>
        @endcan
        @can('kelola_iuran')
            <a href="{{ route('iuran.index') }}" class="btn btn-sm text-white" style="background-color: #059669;">Catat Iuran</a>
        @endcan
        @can('kelola_surat')
            <a href="{{ route('surat.create') }}" class="btn btn-sm text-white" style="background-color: #0d9488;">Buat Surat</a>
        @endcan
        @can('lihat_kas')
            <a href="{{ route('kas.index') }}" class="btn btn-sm btn-outline-success">Buku Kas</a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid #0f766e !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Keluarga</div>
                    <div class="h3 mb-0 text-dark">{{ number_format($ringkasan['total_keluarga'], 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">{{ $ringkasan['total_keluarga'] }} keluarga aktif</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid #059669 !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Jiwa</div>
                    <div class="h3 mb-0 text-dark">{{ number_format($ringkasan['total_jiwa'], 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">{{ $ringkasan['total_jiwa'] }} warga aktif</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid #d97706 !important;">
                <div class="card-body">
                    <div class="text-muted small">Tunggakan Bulan Ini</div>
                    <div class="h3 mb-0 text-dark">{{ FormatUang::ringkas($t['total_sisa']) }}</div>
                    <div class="small text-muted mt-1">{{ FormatUang::penuh($t['total_sisa']) }} · {{ $t['jumlah_keluarga'] }} KK menunggak</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid #0f766e !important;">
                <div class="card-body">
                    <div class="text-muted small">Saldo Kas Tunai</div>
                    <div class="h3 mb-0 text-dark">{{ FormatUang::ringkas($ringkasan['saldo_kas_tunai']) }}</div>
                    <div class="small text-muted mt-1">{{ FormatUang::penuh($ringkasan['saldo_kas_tunai']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid #059669 !important;">
                <div class="card-body">
                    <div class="text-muted small">Saldo Kas Bank</div>
                    <div class="h3 mb-0 text-dark">{{ FormatUang::ringkas($ringkasan['saldo_kas_bank']) }}</div>
                    <div class="small text-muted mt-1">{{ FormatUang::penuh($ringkasan['saldo_kas_bank']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid #0d9488 !important;">
                <div class="card-body">
                    <div class="text-muted small">Surat Terbit Bulan Ini</div>
                    <div class="h3 mb-0 text-dark">{{ number_format($ringkasan['surat_terbit_bulan_ini'], 0, ',', '.') }}</div>
                    <div class="small text-muted mt-1">{{ $ringkasan['surat_terbit_bulan_ini'] }} surat diterbitkan</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-top: 4px solid #0f766e !important;">
        <div class="card-header bg-white border-0 pb-0">
            <h2 class="h5 mb-0" style="color: #0f766e;">Iuran 6 Bulan Terakhir</h2>
            <p class="small text-muted mb-0">Perbandingan total tagihan dan total terbayar</p>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3 small">
                <span class="d-inline-flex align-items-center gap-1">
                    <span class="d-inline-block rounded" style="width: 14px; height: 14px; background: #0f766e;"></span>
                    Total tagihan
                </span>
                <span class="d-inline-flex align-items-center gap-1">
                    <span class="d-inline-block rounded" style="width: 14px; height: 14px; background: #34d399;"></span>
                    Total terbayar
                </span>
            </div>
            <div class="ewarga-grafik-bulan" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 0.35rem; min-height: 180px; padding-top: 0.5rem;">
                @foreach ($ringkasan['grafik_enam_bulan'] as $bulan)
                    <div class="flex-fill text-center" style="min-width: 0;">
                        <div class="d-flex justify-content-center align-items-flex-end gap-1 mx-auto" style="height: 140px; max-width: 72px; align-items: flex-end;">
                            <div title="Tagihan {{ FormatUang::penuh($bulan['total_tagihan']) }}"
                                 style="width: 38%; background: #0f766e; border-radius: 4px 4px 0 0; height: {{ max($bulan['tinggi_tagihan'], $bulan['total_tagihan'] !== '0.00' ? 4 : 0) }}%; min-height: {{ $bulan['total_tagihan'] !== '0.00' ? '4px' : '0' }};"></div>
                            <div title="Terbayar {{ FormatUang::penuh($bulan['total_terbayar']) }}"
                                 style="width: 38%; background: #34d399; border-radius: 4px 4px 0 0; height: {{ max($bulan['tinggi_terbayar'], $bulan['total_terbayar'] !== '0.00' ? 4 : 0) }}%; min-height: {{ $bulan['total_terbayar'] !== '0.00' ? '4px' : '0' }};"></div>
                        </div>
                        <div class="small text-muted mt-2" style="font-size: 0.7rem;">{{ $bulan['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@stop
