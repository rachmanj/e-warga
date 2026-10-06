@extends('adminlte::page')

@section('title', 'Surat Pengantar')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Surat Pengantar</h1>
        @can('kelola_surat')
            <a href="{{ route('surat.create') }}" class="btn btn-success" style="background-color: #059669; border-color: #059669;">
                <i class="bi bi-plus-lg me-1"></i> Ajukan surat
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
            <form method="get" action="{{ route('surat.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach (['draft' => 'Draft', 'diajukan' => 'Diajukan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'terbit' => 'Terbit', 'batal' => 'Batal'] as $val => $label)
                            <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis surat</label>
                    <select name="surat_jenis_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($jenisList as $jenis)
                            <option value="{{ $jenis->id }}" @selected((string) ($filters['surat_jenis_id'] ?? '') === (string) $jenis->id)>{{ $jenis->kode }} — {{ $jenis->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($tahunList as $th)
                            <option value="{{ $th }}" @selected((string) ($filters['tahun'] ?? '') === (string) $th)>{{ $th }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cari nama pemohon</label>
                    <input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Nama warga">
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
                        <th>Nomor</th>
                        <th>Jenis</th>
                        <th>Pemohon</th>
                        <th>Tgl. ajuan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($surat as $item)
                        <tr>
                            <td>{{ $item->nomor_lengkap ?? '—' }}</td>
                            <td>{{ $item->jenis?->nama ?? '—' }}</td>
                            <td>{{ $item->warga?->nama ?? '—' }}</td>
                            <td>{{ $item->tanggal_ajuan?->format('d/m/Y') }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($item->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('surat.show', $item) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada pengajuan surat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($surat->hasPages())
            <div class="card-footer">{{ $surat->links() }}</div>
        @endif
    </div>
@stop
