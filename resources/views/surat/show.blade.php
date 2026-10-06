@extends('adminlte::page')

@section('title', 'Detail Surat')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="mb-0">Detail Surat</h1>
        <a href="{{ route('surat.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke daftar</a>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-header" style="background-color: #ecfdf5;">
                    <strong>{{ $surat->jenis?->nama ?? 'Surat' }}</strong>
                    <span class="badge text-bg-secondary ms-2">{{ ucfirst($surat->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nomor lengkap</dt>
                        <dd class="col-sm-8">{{ $surat->nomor_lengkap ?? '—' }}</dd>
                        <dt class="col-sm-4">Pemohon</dt>
                        <dd class="col-sm-8">{{ $surat->warga?->nama ?? '—' }}</dd>
                        <dt class="col-sm-4">Keperluan</dt>
                        <dd class="col-sm-8">{{ $surat->keperluan }}</dd>
                        <dt class="col-sm-4">Tanggal ajuan</dt>
                        <dd class="col-sm-8">{{ $surat->tanggal_ajuan?->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Tanggal terbit</dt>
                        <dd class="col-sm-8">{{ $surat->tanggal_terbit?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-sm-4">Dibuat oleh</dt>
                        <dd class="col-sm-8">{{ $surat->dibuatOleh?->nama ?? '—' }}</dd>
                        @if ($surat->disetujuiOleh)
                            <dt class="col-sm-4">Disetujui</dt>
                            <dd class="col-sm-8">{{ $surat->disetujuiOleh->nama }} ({{ $surat->disetujui_at?->format('d/m/Y H:i') }})</dd>
                        @endif
                        @if ($surat->status === 'ditolak')
                            <dt class="col-sm-4">Alasan tolak</dt>
                            <dd class="col-sm-8 text-danger">{{ $surat->alasan_tolak }}</dd>
                        @endif
                        @if ($surat->isTerbit())
                            <dt class="col-sm-4">Verifikasi</dt>
                            <dd class="col-sm-8"><code>{{ url('/verifikasi/'.$surat->kode_verifikasi) }}</code></dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Riwayat catatan</strong></div>
                <div class="card-body">
                    @forelse ($surat->catatan as $cat)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="small text-muted">{{ $cat->created_at->format('d/m/Y H:i') }} — {{ $cat->pengguna?->nama ?? 'Sistem' }}</div>
                            <div>{{ $cat->catatan }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada catatan.</p>
                    @endforelse
                </div>
            </div>

            @can('kelola_surat')
                <div class="card mb-3">
                    <div class="card-header"><strong>Tambah catatan</strong></div>
                    <div class="card-body">
                        <form method="post" action="{{ route('surat.catatan', $surat) }}">
                            @csrf
                            <textarea name="catatan" class="form-control mb-2" rows="2" required placeholder="Catatan internal"></textarea>
                            <button type="submit" class="btn btn-sm text-white" style="background-color: #0f766e; border-color: #0f766e;">Simpan catatan</button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-success">
                <div class="card-header"><strong>Aksi</strong></div>
                <div class="card-body d-grid gap-2">
                    @if ($surat->isTerbit() && $surat->file_path)
                        @can('lihat_surat')
                            <a href="{{ route('surat.pdf', $surat) }}" class="btn text-white" style="background-color: #0f766e; border-color: #0f766e;">
                                Unduh PDF
                            </a>
                        @endcan
                    @endif

                    @can('kelola_surat')
                        @if ($surat->status === 'diajukan')
                            <form method="post" action="{{ route('surat.setujui', $surat) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100" style="background-color: #059669; border-color: #059669;">Setujui</button>
                            </form>
                        @endif

                        @if (in_array($surat->status, ['diajukan', 'disetujui'], true))
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="collapse" data-bs-target="#form-tolak">Tolak</button>
                            <div class="collapse" id="form-tolak">
                                <form method="post" action="{{ route('surat.tolak', $surat) }}" class="mt-2">
                                    @csrf
                                    <textarea name="alasan_tolak" class="form-control mb-2" rows="3" required placeholder="Alasan penolakan"></textarea>
                                    <button type="submit" class="btn btn-danger btn-sm w-100">Kirim penolakan</button>
                                </form>
                            </div>
                        @endif
                    @endcan

                    @can('terbitkan_surat')
                        @if (in_array($surat->status, ['diajukan', 'disetujui'], true))
                            <form method="post" action="{{ route('surat.terbitkan', $surat) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">Terbitkan surat</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
@stop
