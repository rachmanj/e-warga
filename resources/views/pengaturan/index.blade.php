@extends('adminlte::page')

@section('title', 'Pengaturan')

@section('content_header')
    <h1>Pengaturan</h1>
@stop

@section('content')
    <p class="mb-3">Kelola akun Anda.</p>
    <a href="{{ route('ubah-sandi.edit') }}" class="btn btn-outline-secondary">
        <i class="bi bi-lock me-1"></i> Ubah kata sandi
    </a>
@stop
