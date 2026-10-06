@extends('adminlte::page')

@section('title', 'Ubah Keluarga')

@section('content_header')
    <h1>Ubah data keluarga</h1>
@stop

@section('content')
    <form method="post" action="{{ route('warga.update', $keluarga) }}">
        @csrf
        @method('PUT')
        <div class="card card-outline card-success mb-3">
            <div class="card-body">
                <p class="text-muted">No. KK saat ini: <code>{{ $keluarga->no_kk_tersamar }}</code></p>
                @include('warga._form_keluarga', ['keluarga' => $keluarga])
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn text-white" style="background-color: #059669; border-color: #059669;">Simpan perubahan</button>
            <a href="{{ route('warga.show', $keluarga) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
@stop
