<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat — e-Warga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%); min-height: 100vh; }
        .card-verifikasi { border-top: 4px solid #0f766e; max-width: 520px; }
        .badge-verified { background-color: #059669; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <div class="card shadow card-verifikasi w-100 mx-3">
        <div class="card-body p-4">
            <h1 class="h4 text-center mb-1" style="color: #0f766e;">Verifikasi Surat</h1>
            <p class="text-center text-muted small mb-4">e-Warga</p>

            <div class="alert alert-success badge-verified text-white text-center mb-4">
                Surat ini terverifikasi dan terdaftar dalam sistem.
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-5">Nomor surat</dt>
                <dd class="col-sm-7"><strong>{{ $surat->nomor_lengkap }}</strong></dd>
                <dt class="col-sm-5">Jenis surat</dt>
                <dd class="col-sm-7">{{ $surat->jenis?->nama }}</dd>
                <dt class="col-sm-5">Nama pemohon</dt>
                <dd class="col-sm-7">{{ $surat->warga?->nama ?? '—' }}</dd>
                <dt class="col-sm-5">Tanggal terbit</dt>
                <dd class="col-sm-7">{{ $surat->tanggal_terbit?->format('d/m/Y') }}</dd>
            </dl>
        </div>
    </div>
</body>
</html>
