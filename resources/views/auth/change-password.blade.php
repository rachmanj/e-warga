@extends('adminlte::page')

@section('title', 'Ubah kata sandi')

@section('content_header')
    <h1>Ubah kata sandi</h1>
@stop

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card card-outline" style="max-width: 32rem;">
        <div class="card-body">
            <form method="post" action="{{ route('ubah-sandi.update') }}">
                @csrf

                <div class="mb-3">
                    <label for="current_password" class="form-label">Kata sandi saat ini</label>
                    <input type="password" name="current_password" id="current_password"
                           class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata sandi baru</label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Konfirmasi kata sandi baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary" style="background-color: #0f766e; border-color: #0f766e;">
                    Simpan kata sandi
                </button>
            </form>
        </div>
    </div>
@stop
