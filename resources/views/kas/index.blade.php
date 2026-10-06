@php use App\Support\FormatUang; @endphp

@extends('adminlte::page')

@section('title', 'Buku Kas')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Buku Kas</h1>
        <a href="{{ route('kas.rekap', ['tahun' => $tahun, 'pos' => $pos]) }}" class="btn btn-sm btn-outline-success">Rekap bulanan</a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row mb-3">
        @foreach (['saldo_awal' => 'Saldo awal', 'total_masuk' => 'Total masuk', 'total_keluar' => 'Total keluar', 'saldo_akhir' => 'Saldo akhir'] as $key => $label)
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card card-outline" style="border-top: 3px solid #0f766e;">
                    <div class="card-body py-2">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="h5 mb-0" title="{{ FormatUang::penuh($buku['ringkasan'][$key]) }}">{{ FormatUang::ringkas($buku['ringkasan'][$key]) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

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

    @can('kelola_kas')
        <div class="row mb-3">
            <div class="col-lg-6">
                <div class="card card-outline card-success">
                    <div class="card-header">Saldo awal</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('kas.saldo-awal.store') }}" class="row g-2">
                            @csrf
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <input type="hidden" name="pos" value="{{ $pos }}">
                            <div class="col-8">
                                <input type="number" step="0.01" name="jumlah" class="form-control" placeholder="Jumlah saldo awal" required>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn w-100 text-white" style="background-color: #059669;">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-outline card-success">
                    <div class="card-header">Transaksi baru</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('kas.transaksi.store') }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="date" name="tanggal" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-4">
                                    <select name="jenis" class="form-select" required>
                                        <option value="masuk">Masuk</option>
                                        <option value="keluar">Keluar</option>
                                    </select>
                                </div>
                                <input type="hidden" name="pos" value="{{ $pos }}">
                                <div class="col-md-4">
                                    <select name="kas_kategori_id" class="form-select">
                                        <option value="">Tanpa kategori</option>
                                        @foreach ($kategori as $kat)
                                            <option value="{{ $kat->id }}">{{ $kat->nama }} ({{ $kat->jenis }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="uraian" class="form-control" placeholder="Uraian" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" step="0.01" name="jumlah" class="form-control" placeholder="Jumlah" required>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn w-100 text-white" style="background-color: #059669;">Tambah</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Uraian</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th class="text-end">Saldo</th>
                        @can('kelola_kas')
                            <th class="text-end">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($buku['baris'] as $baris)
                        <tr @if ($baris['jenis_baris'] === 'saldo_awal') class="table-light fw-semibold" @endif>
                            <td>{{ $baris['tanggal'] ? \Illuminate\Support\Carbon::parse($baris['tanggal'])->format('d/m/Y') : '—' }}</td>
                            <td>{{ $baris['uraian'] }}</td>
                            <td class="text-end text-success">
                                @if ($baris['jenis'] === 'masuk'){{ FormatUang::penuh($baris['jumlah']) }}@endif
                            </td>
                            <td class="text-end text-danger">
                                @if ($baris['jenis'] === 'keluar'){{ FormatUang::penuh($baris['jumlah']) }}@endif
                            </td>
                            <td class="text-end">{{ FormatUang::penuh($baris['saldo_berjalan']) }}</td>
                            @can('kelola_kas')
                                <td class="text-end">
                                    @if ($baris['jenis_baris'] === 'transaksi' && empty($baris['iuran_pembayaran_id']))
                                        <form method="post" action="{{ route('kas.transaksi.destroy', $baris['id']) }}" class="d-inline" onsubmit="return confirm('Hapus transaksi {{ $baris['uraian'] }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-2 d-none" id="saldo-akhir-service">{{ FormatUang::penuh($buku['ringkasan']['saldo_akhir']) }}</p>
@stop
