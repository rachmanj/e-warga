@extends('adminlte::page')

@section('title', 'Tambah Keluarga')

@section('content_header')
    <h1>Tambah keluarga</h1>
@stop

@section('content')
    <form method="post" action="{{ route('warga.store') }}">
        @csrf
        <div class="card card-outline card-success mb-3">
            <div class="card-header"><strong>Data KK</strong></div>
            <div class="card-body">
                @include('warga._form_keluarga')
            </div>
        </div>

        <div class="card card-outline card-success mb-3">
            <div class="card-header"><strong>Kepala keluarga</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" required>
                        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIK</label>
                        <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" maxlength="16" inputmode="numeric" value="{{ old('nik') }}">
                        @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Jenis kelamin <span class="text-danger">*</span></label>
                        <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror" required>
                            <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                        </select>
                        @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control" value="{{ old('pekerjaan') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Agama</label>
                        <input type="text" name="agama" class="form-control" value="{{ old('agama') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status perkawinan</label>
                        <input type="text" name="status_perkawinan" class="form-control" value="{{ old('status_perkawinan') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn text-white" style="background-color: #059669; border-color: #059669;">Simpan</button>
            <a href="{{ route('warga.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
@stop
