@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $loginUrl = $layoutHelper->makeUrl(route('login', absolute: false));
@endphp

@section('auth_header', 'Masuk ke e-Warga')

@section('auth_body')
    <form action="{{ $loginUrl }}" method="post">
        @csrf

        <label for="username" class="visually-hidden">Nama pengguna</label>

        <div class="input-group mb-3">
            <input type="text" name="username" id="username"
                class="form-control @error('username') is-invalid @enderror"
                value="{{ old('username') }}" placeholder="Nama pengguna" autofocus autocomplete="username">

            <div class="input-group-text">
                <span class="bi bi-person {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            @error('username')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <label for="password" class="visually-hidden">Kata sandi</label>

        <div class="input-group mb-3">
            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Kata sandi" autocomplete="current-password">

            <div class="input-group-text">
                <span class="bi bi-lock-fill {{ config('adminlte.classes_auth_icon', '') }}"></span>
            </div>

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember"
                           id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Ingat saya
                    </label>
                </div>
            </div>

            <div class="col-5">
                <div class="d-grid">
                    <button type="submit" class="btn {{ config('adminlte.classes_auth_btn', 'btn-primary') }}" style="background-color: #0f766e;">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Masuk
                    </button>
                </div>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
@stop
