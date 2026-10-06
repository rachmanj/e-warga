@php
    use App\Support\FormatUang;
    $namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp

@extends('adminlte::page')

@section('title', 'Rekap Kas Bulanan')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Rekap Kas Bulanan</h1>
        <a href="{{ route('kas.index', ['tahun' => $tahun, 'pos' => $pos]) }}" class="btn btn-sm btn-outline-secondary">Buku kas</a>
    </div>
@stop

@section('content')
    <div class="card card-outline mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="{{ $tahun }}" min="2000" max="2100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pos</label>
                    <select name="pos" class="form-select">
                        <option value="tunai" @selected($pos === 'tunai')>Tunai</option>
                        <option value="bank" @selected($pos === 'bank')>Bank</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100 text-white" style="background-color: #0f766e;">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Saldo akhir bulan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekap as $row)
                        <tr>
                            <td>{{ $namaBulan[$row['bulan'] - 1] }}</td>
                            <td class="text-end">{{ FormatUang::penuh($row['total_masuk']) }}</td>
                            <td class="text-end">{{ FormatUang::penuh($row['total_keluar']) }}</td>
                            <td class="text-end fw-semibold">{{ FormatUang::penuh($row['saldo']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
