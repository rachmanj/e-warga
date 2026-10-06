@php use App\Support\FormatUang; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $kwitansi?->nomor }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #111; }
        .kop { text-align: center; border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 24px; }
        .kop h2 { margin: 0; font-size: 16pt; color: #0f766e; }
        .kop p { margin: 2px 0; font-size: 11pt; }
        h1 { text-align: center; font-size: 14pt; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        td { padding: 6px 4px; vertical-align: top; }
        .label { width: 35%; font-weight: bold; }
        .jumlah { font-size: 14pt; font-weight: bold; color: #059669; }
        .ttd { margin-top: 48px; text-align: right; width: 50%; float: right; }
    </style>
</head>
<body>
    @if ($rt)
        <div class="kop">
            <h2>{{ $rt->nama }}</h2>
            <p>RW {{ $rt->rw }} · Kel. {{ $rt->kelurahan }} · Kec. {{ $rt->kecamatan }}</p>
            <p>{{ $rt->kota }}</p>
        </div>
    @endif

    <h1>KWITANSI</h1>
    <p style="text-align:center;"><strong>Nomor: {{ $kwitansi?->nomor }}</strong></p>

    <table>
        <tr>
            <td class="label">Sudah terima dari</td>
            <td>{{ $kepala?->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>{{ $tagihan->keluarga?->alamat ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Untuk pembayaran</td>
            <td>{{ $tagihan->jenis?->nama }} — periode {{ $tagihan->periode }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah</td>
            <td class="jumlah">{{ FormatUang::penuh($pembayaran->jumlah) }}</td>
        </tr>
        <tr>
            <td class="label">Metode</td>
            <td>{{ ucfirst($pembayaran->metode) }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>{{ $pembayaran->tanggal?->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <div class="ttd">
        <p>Bendahara {{ $rt?->nama }}</p>
        <br><br><br>
        @if ($rt?->nama_bendahara)
            <p><strong><u>{{ $rt->nama_bendahara }}</u></strong></p>
        @else
            <p>_________________________</p>
        @endif
    </div>
</body>
</html>
