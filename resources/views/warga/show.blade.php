@extends('adminlte::page')

@section('title', 'Detail Keluarga')

@php
    $hubunganLabel = ['kepala' => 'Kepala', 'istri' => 'Istri', 'anak' => 'Anak', 'famili' => 'Famili', 'lain' => 'Lainnya'];
    $mutasiLabel = ['masuk' => 'Masuk', 'keluar' => 'Keluar', 'lahir' => 'Lahir', 'meninggal' => 'Meninggal', 'ubah_kk' => 'Ubah KK'];
    $dokumenLabel = ['ktp' => 'KTP', 'kk' => 'KK', 'lainnya' => 'Lainnya'];
@endphp

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Detail keluarga</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('warga.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            @if ($canKelola)
                <a href="{{ route('warga.edit', $keluarga) }}" class="btn btn-sm text-white" style="background-color: #0f766e; border-color: #0f766e;">Ubah KK</a>
            @endif
        </div>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card card-outline card-success mb-3">
        <div class="card-header"><strong>Kartu Keluarga</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">No. KK</dt>
                <dd class="col-sm-9"><code>{{ $keluarga->no_kk_tersamar }}</code></dd>
                <dt class="col-sm-3">Alamat</dt>
                <dd class="col-sm-9">{{ $keluarga->alamat }}</dd>
                <dt class="col-sm-3">Blok / unit</dt>
                <dd class="col-sm-9">{{ $keluarga->blok_unit ?? '—' }}</dd>
                <dt class="col-sm-3">RT lingkungan</dt>
                <dd class="col-sm-9">{{ $keluarga->rt_lingkungan ?? '—' }}</dd>
                <dt class="col-sm-3">Status hunian</dt>
                <dd class="col-sm-9">{{ ucfirst($keluarga->status_hunian) }}</dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">{{ ucfirst($keluarga->status) }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Anggota keluarga</strong>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Hubungan</th>
                        <th>JK</th>
                        <th>Status</th>
                        @if ($canKelola)<th class="text-end">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($keluarga->warga as $anggota)
                        <tr>
                            <td>{{ $anggota->nama }}</td>
                            <td><code>{{ $anggota->nik_tersamar ?? '—' }}</code></td>
                            <td>{{ $hubunganLabel[$anggota->hubungan] ?? $anggota->hubungan }}</td>
                            <td>{{ $anggota->jenis_kelamin }}</td>
                            <td>{{ ucfirst($anggota->status) }}</td>
                            @if ($canKelola)
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-anggota-{{ $anggota->id }}">Ubah</button>
                                    <form method="post" action="{{ route('warga.anggota.destroy', $anggota) }}" class="d-inline" onsubmit="return confirm('Hapus anggota ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                        @if ($canKelola)
                            <tr class="collapse" id="edit-anggota-{{ $anggota->id }}">
                                <td colspan="6" class="bg-light">
                                    <form method="post" action="{{ route('warga.anggota.update', $anggota) }}" class="p-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <input type="text" name="nama" class="form-control form-control-sm" value="{{ $anggota->nama }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="nik" class="form-control form-control-sm" placeholder="NIK baru (opsional)" maxlength="16">
                                            </div>
                                            <div class="col-md-2">
                                                <select name="hubungan" class="form-select form-select-sm" required>
                                                    @foreach ($hubunganLabel as $val => $label)
                                                        <option value="{{ $val }}" @selected($anggota->hubungan === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <select name="jenis_kelamin" class="form-select form-select-sm">
                                                    <option value="L" @selected($anggota->jenis_kelamin === 'L')>L</option>
                                                    <option value="P" @selected($anggota->jenis_kelamin === 'P')>P</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="status" class="form-select form-select-sm">
                                                    @foreach (['aktif', 'pindah', 'meninggal'] as $st)
                                                        <option value="{{ $st }}" @selected($anggota->status === $st)>{{ ucfirst($st) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-sm text-white w-100" style="background-color: #0f766e;">Simpan</button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($canKelola)
            <div class="card-footer">
                <strong class="d-block mb-2">Tambah anggota</strong>
                <form method="post" action="{{ route('warga.anggota.store', $keluarga) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small">Nama</label>
                        <input type="text" name="nama" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">NIK</label>
                        <input type="text" name="nik" class="form-control form-control-sm" maxlength="16">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Hubungan</label>
                        <select name="hubungan" class="form-select form-select-sm" required>
                            @foreach ($hubunganLabel as $val => $label)
                                @if ($val !== 'kepala')
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">JK</label>
                        <select name="jenis_kelamin" class="form-select form-select-sm">
                            <option value="L">L</option>
                            <option value="P">P</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm text-white w-100" style="background-color: #059669;">Tambah</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Riwayat mutasi</strong></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Warga</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($keluarga->mutasi as $m)
                        <tr>
                            <td>{{ $m->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $mutasiLabel[$m->jenis] ?? $m->jenis }}</td>
                            <td>{{ $m->warga?->nama ?? '—' }}</td>
                            <td>{{ $m->keterangan ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canKelola)
            <div class="card-footer">
                <form method="post" action="{{ route('warga.mutasi.store', $keluarga) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small">Jenis</label>
                        <select name="jenis" class="form-select form-select-sm" required>
                            @foreach ($mutasiLabel as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Warga (opsional)</label>
                        <select name="warga_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach ($keluarga->warga as $anggota)
                                <option value="{{ $anggota->id }}">{{ $anggota->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm text-white w-100" style="background-color: #0f766e;">Catat mutasi</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Dokumen</strong></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Nama berkas</th>
                        <th>Warga</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($keluarga->dokumen as $doc)
                        <tr>
                            <td>{{ $dokumenLabel[$doc->jenis] ?? $doc->jenis }}</td>
                            <td>{{ $doc->nama_asli }}</td>
                            <td>{{ $doc->warga?->nama ?? 'Keluarga' }}</td>
                            <td class="text-end">
                                @if ($canLihatDokumen)
                                    <a href="{{ route('dokumen.berkas', $doc) }}" class="btn btn-sm btn-outline-secondary">Unduh</a>
                                @endif
                                @if ($canKelolaDokumen)
                                    <form method="post" action="{{ route('dokumen.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Hapus dokumen ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">Belum ada dokumen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canKelolaDokumen)
            <div class="card-footer">
                <form method="post" action="{{ route('warga.dokumen.store', $keluarga) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small">Jenis</label>
                        <select name="jenis" class="form-select form-select-sm" required>
                            @foreach ($dokumenLabel as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Warga (opsional)</label>
                        <select name="warga_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach ($keluarga->warga as $anggota)
                                <option value="{{ $anggota->id }}">{{ $anggota->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Berkas</label>
                        <input type="file" name="berkas" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm text-white w-100" style="background-color: #059669;">Unggah</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    @can('kelola_warga')
        <form method="post" action="{{ route('warga.destroy', $keluarga) }}" onsubmit="return confirm('Hapus seluruh data keluarga ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Hapus keluarga</button>
        </form>
    @endcan
@stop
