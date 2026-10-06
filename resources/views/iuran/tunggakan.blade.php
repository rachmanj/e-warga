@php use App\Support\FormatUang; @endphp

@extends('adminlte::page')

@section('title', 'Tunggakan Iuran')

@section('content_header')
    <h1>Tunggakan Iuran</h1>
    <p class="text-muted mb-0">Periode sampai {{ $periodeHingga }}</p>
@stop

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Kepala keluarga</th>
                        <th>Alamat</th>
                        <th class="text-center">Periode menunggak</th>
                        <th class="text-end">Total tunggakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $row)
                        <tr>
                            <td>{{ $row['nama_kepala'] }}</td>
                            <td>{{ $row['alamat'] }}</td>
                            <td class="text-center">{{ $row['jumlah_periode'] }}</td>
                            <td class="text-end">{{ FormatUang::penuh($row['total_tunggakan']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Tidak ada tunggakan untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
