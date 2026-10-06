@php use App\Support\FormatUang; @endphp

@extends('adminlte::page')

@section('title', 'Tagihan Iuran')

@section('content_header')
    <h1>Tagihan Iuran</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @can('kelola_iuran')
        <div class="card card-outline card-success mb-3">
            <div class="card-header">Buat tagihan periode</div>
            <div class="card-body">
                <form method="post" action="{{ route('iuran.tagihan.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Jenis iuran</label>
                        <select name="iuran_jenis_id" class="form-select" required>
                            @foreach ($jenisList as $j)
                                <option value="{{ $j->id }}">{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periode (YYYY-MM)</label>
                        <input type="month" name="periode" class="form-control" required value="{{ $filters['periode'] ?? now()->format('Y-m') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jatuh tempo (opsional)</label>
                        <input type="date" name="jatuh_tempo" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn w-100 text-white" style="background-color: #059669; border-color: #059669;">Generate</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="card card-outline mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <input type="month" name="periode" class="form-control" value="{{ $filters['periode'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis</label>
                    <select name="iuran_jenis_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($jenisList as $j)
                            <option value="{{ $j->id }}" @selected(($filters['iuran_jenis_id'] ?? '') == $j->id)>{{ $j->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['belum', 'sebagian', 'lunas', 'bebas'] as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100 text-white" style="background-color: #0f766e; border-color: #0f766e;">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Keluarga</th>
                        <th>Jenis</th>
                        <th class="text-end">Nominal</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tagihan as $row)
                        @php $ef = $efektifPerId[$row->id]; @endphp
                        <tr>
                            <td>{{ $row->periode }}</td>
                            <td>{{ $row->keluarga?->warga->firstWhere('hubungan', 'kepala')?->nama ?? '—' }}</td>
                            <td>{{ $row->jenis?->nama }}</td>
                            <td class="text-end">{{ FormatUang::penuh($ef['nominal']) }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($row->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('iuran.tagihan.show', $row) }}" class="btn btn-sm btn-outline-success">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($tagihan->hasPages())
            <div class="card-footer">{{ $tagihan->links() }}</div>
        @endif
    </div>
@stop
