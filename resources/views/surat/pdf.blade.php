<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat {{ $surat->nomor_lengkap }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.5; color: #111; }
        .kop { text-align: center; border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 20px; }
        .kop h2 { margin: 0; font-size: 16pt; color: #0f766e; }
        .kop p { margin: 2px 0; font-size: 11pt; }
        .nomor { text-align: center; margin: 16px 0; font-weight: bold; }
        .isi { text-align: justify; margin: 20px 0; }
        .ttd { margin-top: 40px; text-align: right; width: 45%; float: right; }
        .verifikasi { clear: both; margin-top: 60px; font-size: 9pt; color: #555; border-top: 1px solid #ccc; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>{{ $rt->nama }}</h2>
        <p>RW {{ $rt->rw }} · Kel. {{ $rt->kelurahan }} · Kec. {{ $rt->kecamatan }}</p>
        <p>{{ $rt->kota }}</p>
    </div>

    <div class="nomor">Nomor: {{ $surat->nomor_lengkap }}</div>

    <div class="isi">{!! $isi !!}</div>

    <div class="ttd">
        <p>{{ $rt->kota }}, {{ $surat->tanggal_terbit?->translatedFormat('d F Y') }}</p>
        <p>Ketua {{ $rt->nama }}</p>
        <br><br><br>
        <p><strong><u>{{ $rt->nama_ketua }}</u></strong></p>
    </div>

    <div class="verifikasi">
        Verifikasi keaslian: {{ $verifikasiUrl }}
    </div>
</body>
</html>
