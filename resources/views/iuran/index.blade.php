@php
    use App\Support\FormatUang;
    $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $kelasStatus = [
        'belum' => 'bg-danger',
        'sebagian' => 'bg-warning text-dark',
        'lunas' => 'bg-success',
        'bebas' => 'bg-secondary',
    ];
@endphp

@extends('adminlte::page')

@section('title', 'Daftar Iuran')

@section('content_header')
    <h1>Daftar Iuran</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-outline" style="border-top: 3px solid #0f766e;">
                <div class="card-body">
                    <div class="text-muted small">Total tagihan</div>
                    <div class="h4 mb-0" title="{{ FormatUang::penuh($ringkasan['total_tagihan']) }}">{{ FormatUang::ringkas($ringkasan['total_tagihan']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-outline" style="border-top: 3px solid #059669;">
                <div class="card-body">
                    <div class="text-muted small">Total terbayar</div>
                    <div class="h4 mb-0" title="{{ FormatUang::penuh($ringkasan['total_terbayar']) }}">{{ FormatUang::ringkas($ringkasan['total_terbayar']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-outline" style="border-top: 3px solid #b45309;">
                <div class="card-body">
                    <div class="text-muted small">Total tunggakan</div>
                    <div class="h4 mb-0" title="{{ FormatUang::penuh($ringkasan['total_tunggakan']) }}">{{ FormatUang::ringkas($ringkasan['total_tunggakan']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-success mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('iuran.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="{{ $tahun }}" min="2000" max="2100">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Jenis iuran</label>
                    <select name="jenis" class="form-select">
                        @foreach ($jenisList as $j)
                            <option value="{{ $j->id }}" @selected($jenisId === $j->id)>{{ $j->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100 text-white" style="background-color: #0f766e; border-color: #0f766e;">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-2 d-flex flex-wrap gap-3 small">
        <span><span class="d-inline-block rounded {{ $kelasStatus['belum'] }}" style="width:14px;height:14px;"></span> Belum</span>
        <span><span class="d-inline-block rounded {{ $kelasStatus['sebagian'] }}" style="width:14px;height:14px;"></span> Sebagian</span>
        <span><span class="d-inline-block rounded {{ $kelasStatus['lunas'] }}" style="width:14px;height:14px;"></span> Lunas</span>
        <span><span class="d-inline-block rounded {{ $kelasStatus['bebas'] }}" style="width:14px;height:14px;"></span> Bebas</span>
        <span><span class="d-inline-block rounded border" style="width:14px;height:14px;background:#f8f9fa;"></span> Tanpa tagihan</span>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kepala keluarga</th>
                        @foreach ($namaBulan as $nb)
                            <th class="text-center">{{ $nb }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grid as $baris)
                        <tr>
                            <td>{{ $baris['nama_kepala'] }}</td>
                            @for ($m = 1; $m <= 12; $m++)
                                @php $st = $baris['bulan'][$m] ?? null; @endphp
                                <td class="text-center p-1">
                                    @if ($st)
                                        <span class="d-block rounded {{ $kelasStatus[$st] ?? '' }}" style="min-width:28px;height:22px;" title="{{ ucfirst($st) }}"></span>
                                    @else
                                        <span class="d-block rounded border bg-light" style="min-width:28px;height:22px;"></span>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">Belum ada data. Pilih jenis iuran atau buat tagihan terlebih dahulu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
