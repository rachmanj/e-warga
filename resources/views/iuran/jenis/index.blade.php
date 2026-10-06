@php use App\Support\FormatUang; @endphp

@extends('adminlte::page')

@section('title', 'Jenis Iuran')

@section('content_header')
    <h1>Jenis Iuran</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <div class="card card-outline card-success mb-4">
        <div class="card-header">Tambah jenis iuran</div>
        <div class="card-body">
            <form method="post" action="{{ route('iuran.jenis.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" required>
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nominal default</label>
                    <input type="number" step="0.01" name="nominal_default" class="form-control @error('nominal_default') is-invalid @enderror" value="{{ old('nominal_default') }}" required>
                    @error('nominal_default')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Periode</label>
                    <select name="periode" class="form-select">
                        <option value="bulanan">Bulanan</option>
                        <option value="tahunan">Tahunan</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" checked id="aktif-baru">
                        <label class="form-check-label" for="aktif-baru">Aktif</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn text-white w-100" style="background-color: #059669; border-color: #059669;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($jenisList as $jenis)
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="{{ route('iuran.jenis.update', $jenis) }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" value="{{ $jenis->nama }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nominal</label>
                        <input type="number" step="0.01" name="nominal_default" class="form-control" value="{{ $jenis->nominal_default }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Periode</label>
                        <select name="periode" class="form-select">
                            <option value="bulanan" @selected($jenis->periode === 'bulanan')>Bulanan</option>
                            <option value="tahunan" @selected($jenis->periode === 'tahunan')>Tahunan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="aktif" value="1" @checked($jenis->aktif) id="aktif-{{ $jenis->id }}">
                            <label class="form-check-label" for="aktif-{{ $jenis->id }}">Aktif</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-success w-100">Perbarui</button>
                    </div>
                </form>
                @if ($jenis->tagihan_count === 0)
                    <form method="post" action="{{ route('iuran.jenis.destroy', $jenis) }}" class="mt-2 text-end" onsubmit="return confirm('Hapus jenis iuran {{ $jenis->nama }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus jenis</button>
                    </form>
                @endif

                <details>
                    <summary class="text-muted small">Tarif khusus keluarga</summary>
                    <form method="post" action="{{ route('iuran.jenis.tarif', $jenis) }}" class="row g-2 mt-2">
                        @csrf
                        <div class="col-md-5">
                            <select name="keluarga_id" class="form-select" required>
                                <option value="">Pilih keluarga</option>
                                @foreach ($keluarga as $kk)
                                    <option value="{{ $kk->id }}">{{ $kk->warga->first()?->nama ?? '—' }} — {{ $kk->alamat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.01" name="nominal" class="form-control" placeholder="Nominal khusus" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm text-white w-100" style="background-color: #0f766e;">Simpan tarif</button>
                        </div>
                    </form>
                </details>
            </div>
        </div>
    @endforeach
@stop
