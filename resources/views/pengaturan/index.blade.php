@extends('adminlte::page')

@section('title', 'Pengaturan')

@section('content_header')
    <h1>Pengaturan</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card card-outline card-secondary mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Akun</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Kelola kata sandi akun Anda.</p>
            <a href="{{ route('ubah-sandi.edit') }}" class="btn btn-outline-secondary">
                <i class="bi bi-lock me-1"></i> Ubah kata sandi
            </a>
        </div>
    </div>

    @can('kelola_rt')
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Data RT</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Data ini ditampilkan pada kop surat dan kwitansi iuran. Pastikan nama pengurus dan alamat sekretariat sudah benar.
                </p>

                <form method="post" action="{{ route('pengaturan.rt.update') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nama" class="form-label">Nama RT <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="nama"
                                   class="form-control @error('nama') is-invalid @enderror"
                                   value="{{ old('nama', $rt?->nama) }}" required maxlength="100">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="rw" class="form-label">RW</label>
                            <input type="text" name="rw" id="rw"
                                   class="form-control @error('rw') is-invalid @enderror"
                                   value="{{ old('rw', $rt?->rw) }}" maxlength="50">
                            @error('rw')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="nama_ketua" class="form-label">Nama ketua</label>
                            <input type="text" name="nama_ketua" id="nama_ketua"
                                   class="form-control @error('nama_ketua') is-invalid @enderror"
                                   value="{{ old('nama_ketua', $rt?->nama_ketua) }}" maxlength="255">
                            @error('nama_ketua')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="nama_sekretaris" class="form-label">Nama sekretaris</label>
                            <input type="text" name="nama_sekretaris" id="nama_sekretaris"
                                   class="form-control @error('nama_sekretaris') is-invalid @enderror"
                                   value="{{ old('nama_sekretaris', $rt?->nama_sekretaris) }}" maxlength="255">
                            @error('nama_sekretaris')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="nama_bendahara" class="form-label">Nama bendahara</label>
                            <input type="text" name="nama_bendahara" id="nama_bendahara"
                                   class="form-control @error('nama_bendahara') is-invalid @enderror"
                                   value="{{ old('nama_bendahara', $rt?->nama_bendahara) }}" maxlength="255">
                            @error('nama_bendahara')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="alamat_sekretariat" class="form-label">Alamat sekretariat</label>
                            <textarea name="alamat_sekretariat" id="alamat_sekretariat" rows="2"
                                      class="form-control @error('alamat_sekretariat') is-invalid @enderror"
                                      maxlength="1000">{{ old('alamat_sekretariat', $rt?->alamat_sekretariat) }}</textarea>
                            @error('alamat_sekretariat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="kode_pos" class="form-label">Kode pos</label>
                            <input type="text" name="kode_pos" id="kode_pos"
                                   class="form-control @error('kode_pos') is-invalid @enderror"
                                   value="{{ old('kode_pos', $rt?->kode_pos) }}" maxlength="5" inputmode="numeric">
                            @error('kode_pos')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="no_hp" class="form-label">Nomor HP</label>
                            <input type="text" name="no_hp" id="no_hp"
                                   class="form-control @error('no_hp') is-invalid @enderror"
                                   value="{{ old('no_hp', $rt?->no_hp) }}" maxlength="20">
                            @error('no_hp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="kelurahan" class="form-label">Kelurahan</label>
                            <input type="text" name="kelurahan" id="kelurahan"
                                   class="form-control @error('kelurahan') is-invalid @enderror"
                                   value="{{ old('kelurahan', $rt?->kelurahan) }}" maxlength="255">
                            @error('kelurahan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="kecamatan" class="form-label">Kecamatan</label>
                            <input type="text" name="kecamatan" id="kecamatan"
                                   class="form-control @error('kecamatan') is-invalid @enderror"
                                   value="{{ old('kecamatan', $rt?->kecamatan) }}" maxlength="255">
                            @error('kecamatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="kota" class="form-label">Kota</label>
                            <input type="text" name="kota" id="kota"
                                   class="form-control @error('kota') is-invalid @enderror"
                                   value="{{ old('kota', $rt?->kota) }}" maxlength="255">
                            @error('kota')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" style="background-color: #0f766e; border-color: #0f766e;">
                            Simpan data RT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@stop
