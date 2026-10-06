@extends('adminlte::page')

@section('title', 'Data Warga')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Data Warga</h1>
        @can('kelola_warga')
            <a href="{{ route('warga.create') }}" class="btn btn-success" style="background-color: #059669; border-color: #059669;">
                <i class="bi bi-plus-lg me-1"></i> Tambah keluarga
            </a>
        @endcan
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card card-outline card-success mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('warga.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status hunian</label>
                    <select name="status_hunian" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['milik' => 'Milik', 'sewa' => 'Sewa', 'kontrak' => 'Kontrak', 'kos' => 'Kos'] as $val => $label)
                            <option value="{{ $val }}" @selected(($filters['status_hunian'] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blok / unit</label>
                    <input type="text" name="blok_unit" class="form-control" value="{{ $filters['blok_unit'] ?? '' }}" placeholder="Contoh: A-12">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cari nama atau alamat</label>
                    <input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Nama kepala atau alamat">
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
                        <th>No. KK</th>
                        <th>Kepala keluarga</th>
                        <th>Anggota</th>
                        <th>Blok / unit</th>
                        <th>Hunian</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($keluarga as $kk)
                        @php $kepala = $kk->warga->first(); @endphp
                        <tr>
                            <td><code>{{ $kk->no_kk_tersamar ?? '—' }}</code></td>
                            <td>{{ $kepala?->nama ?? '—' }}</td>
                            <td>{{ $kk->warga_count }}</td>
                            <td>{{ $kk->blok_unit ?? '—' }}</td>
                            <td>{{ ucfirst($kk->status_hunian) }}</td>
                            <td>{{ ucfirst($kk->status) }}</td>
                            <td class="text-end">
                                <a href="{{ route('warga.show', $kk) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                @can('kelola_warga')
                                    <a href="{{ route('warga.edit', $kk) }}" class="btn btn-sm text-white" style="background-color: #0f766e; border-color: #0f766e;">Ubah</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada data keluarga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($keluarga->hasPages())
            <div class="card-footer">{{ $keluarga->links() }}</div>
        @endif
    </div>
@stop
