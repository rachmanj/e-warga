@php use App\Support\FormatUang; @endphp

@extends('adminlte::page')

@section('title', 'Detail Tagihan')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Detail Tagihan</h1>
        <a href="{{ route('iuran.tagihan.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-outline mb-3" style="border-top: 3px solid #0f766e;">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Periode</dt>
                        <dd class="col-sm-8">{{ $tagihan->periode }}</dd>
                        <dt class="col-sm-4">Jenis</dt>
                        <dd class="col-sm-8">{{ $tagihan->jenis?->nama }}</dd>
                        <dt class="col-sm-4">Keluarga</dt>
                        <dd class="col-sm-8">{{ $tagihan->keluarga?->warga->firstWhere('hubungan', 'kepala')?->nama ?? '—' }}</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">{{ ucfirst($tagihan->status) }}</dd>
                        <dt class="col-sm-4">Nominal</dt>
                        <dd class="col-sm-8">{{ FormatUang::penuh($efektif['nominal']) }}</dd>
                        <dt class="col-sm-4">Terbayar</dt>
                        <dd class="col-sm-8">{{ FormatUang::penuh($efektif['terbayar']) }}</dd>
                        <dt class="col-sm-4">Sisa</dt>
                        <dd class="col-sm-8 fw-bold">{{ FormatUang::penuh($efektif['sisa']) }}</dd>
                        @if ($tagihan->status === 'bebas')
                            <dt class="col-sm-4">Alasan bebas</dt>
                            <dd class="col-sm-8">{{ $tagihan->alasan_bebas }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @can('kelola_iuran')
                @if (! in_array($tagihan->status, ['lunas', 'bebas'], true) && bccomp($efektif['sisa'], '0', 2) > 0)
                    <div class="card card-outline card-success mb-3">
                        <div class="card-header">Catat pembayaran</div>
                        <div class="card-body">
                            <form method="post" action="{{ route('iuran.tagihan.pembayaran', $tagihan) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', now()->toDateString()) }}" required>
                                    @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Jumlah (maks. {{ FormatUang::penuh($efektif['sisa']) }})</label>
                                    <input type="number" step="0.01" name="jumlah" class="form-control @error('jumlah') is-invalid @enderror" value="{{ old('jumlah') }}" required>
                                    @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Metode</label>
                                    <select name="metode" class="form-select" required>
                                        <option value="tunai">Tunai</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">No. referensi</label>
                                    <input type="text" name="no_referensi" class="form-control" value="{{ old('no_referensi') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Bukti pembayaran</label>
                                    <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                                <button type="submit" class="btn text-white" style="background-color: #059669; border-color: #059669;">Simpan pembayaran</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if ($tagihan->status !== 'bebas' && $tagihan->status !== 'lunas')
                    <div class="card card-outline mb-3">
                        <div class="card-header">Bebaskan tagihan</div>
                        <div class="card-body">
                            <form method="post" action="{{ route('iuran.tagihan.bebas', $tagihan) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Alasan</label>
                                    <textarea name="alasan" class="form-control" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-secondary">Bebaskan</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Riwayat pembayaran</div>
                <div class="card-body table-responsive p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Jumlah</th>
                                <th>Metode</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tagihan->pembayaran as $bayar)
                                <tr>
                                    <td>{{ $bayar->tanggal?->format('d/m/Y') }}</td>
                                    <td class="text-end">{{ FormatUang::penuh($bayar->jumlah) }}</td>
                                    <td>{{ ucfirst($bayar->metode) }}</td>
                                    <td class="text-end">
                                        @if ($bayar->kwitansi)
                                            <a href="{{ route('iuran.pembayaran.kwitansi', $bayar) }}" class="btn btn-sm btn-outline-primary">Kwitansi</a>
                                        @endif
                                        @can('kelola_iuran')
                                            <form method="post" action="{{ route('iuran.pembayaran.destroy', $bayar) }}" class="d-inline" onsubmit="return confirm('Hapus pembayaran {{ FormatUang::penuh($bayar->jumlah) }} pada {{ $bayar->tanggal?->format('d/m/Y') }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">Belum ada pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
