@php
    $hunianOptions = ['milik' => 'Milik', 'sewa' => 'Sewa', 'kontrak' => 'Kontrak', 'kos' => 'Kos'];
    $statusOptions = ['aktif' => 'Aktif', 'pindah' => 'Pindah', 'nonaktif' => 'Nonaktif'];
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nomor KK <span class="text-danger">*</span></label>
        <input type="text" name="no_kk" class="form-control @error('no_kk') is-invalid @enderror" maxlength="16" inputmode="numeric" pattern="\d{16}" value="{{ old('no_kk') }}" required autocomplete="off">
        @error('no_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">16 digit angka. Tidak ditampilkan penuh setelah disimpan.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Status hunian <span class="text-danger">*</span></label>
        <select name="status_hunian" class="form-select @error('status_hunian') is-invalid @enderror" required>
            @foreach ($hunianOptions as $val => $label)
                <option value="{{ $val }}" @selected(old('status_hunian', isset($keluarga) ? $keluarga->status_hunian : '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status_hunian')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Alamat <span class="text-danger">*</span></label>
        <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="2" required>{{ old('alamat', $keluarga->alamat ?? '') }}</textarea>
        @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Blok / unit</label>
        <input type="text" name="blok_unit" class="form-control" value="{{ old('blok_unit', $keluarga->blok_unit ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">RT lingkungan</label>
        <input type="text" name="rt_lingkungan" class="form-control" value="{{ old('rt_lingkungan', $keluarga->rt_lingkungan ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Nama pemilik (jika bukan milik)</label>
        <input type="text" name="nama_pemilik" class="form-control" value="{{ old('nama_pemilik', $keluarga->nama_pemilik ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Tanggal masuk</label>
        <input type="date" name="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk', isset($keluarga) && $keluarga->tanggal_masuk ? $keluarga->tanggal_masuk->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Tanggal keluar</label>
        <input type="date" name="tanggal_keluar" class="form-control @error('tanggal_keluar') is-invalid @enderror" value="{{ old('tanggal_keluar', isset($keluarga) && $keluarga->tanggal_keluar ? $keluarga->tanggal_keluar->format('Y-m-d') : '') }}">
        @error('tanggal_keluar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Status keluarga</label>
        <select name="status" class="form-select">
            @foreach ($statusOptions as $val => $label)
                <option value="{{ $val }}" @selected(old('status', isset($keluarga) ? $keluarga->status : 'aktif') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $keluarga->keterangan ?? '') }}</textarea>
    </div>
</div>
