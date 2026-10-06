@extends('adminlte::page')

@section('title', 'Dasbor')

@section('content_header')
    <h1>Dasbor</h1>
@stop

@section('content')
    <p class="text-muted mb-4">RT aktif: <strong>{{ $rtNama }}</strong></p>

    <div class="row">
        <div class="col-md-3 col-sm-6 mb-3">
            <x-adminlte-small-box title="{{ $totalKk }}" text="Total KK" icon="bi bi-house-door" theme="primary"/>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <x-adminlte-small-box title="{{ $totalJiwa }}" text="Total jiwa" icon="bi bi-people" theme="success"/>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <x-adminlte-small-box title="{{ $tunggakanBulanIni }}" text="Tunggakan bulan ini" icon="bi bi-exclamation-circle" theme="warning"/>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <x-adminlte-small-box title="{{ $saldoKas }}" text="Saldo kas" icon="bi bi-wallet2" theme="info"/>
        </div>
    </div>
@stop
