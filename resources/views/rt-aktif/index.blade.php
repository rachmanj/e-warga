@extends('adminlte::page')

@section('title', 'Pilih RT aktif')

@section('content_header')
    <h1>Pilih RT aktif</h1>
@stop

@section('content')
    <p class="text-muted">Sebagai superadmin, pilih RT yang akan dikelola pada sesi ini.</p>

    <div class="card card-outline" style="max-width: 28rem;">
        <div class="card-body">
            <form method="post" action="{{ route('rt-aktif.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="rt_id" class="form-label">RT</label>
                    <select name="rt_id" id="rt_id" class="form-select @error('rt_id') is-invalid @enderror" required>
                        <option value="">— Pilih RT —</option>
                        @foreach ($rts as $rt)
                            <option value="{{ $rt->id }}" @selected($rtAktifId == $rt->id)>
                                {{ $rt->nama }} (RW {{ $rt->rw }}, {{ $rt->kelurahan }})
                            </option>
                        @endforeach
                    </select>
                    @error('rt_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="background-color: #0f766e; border-color: #0f766e;">
                    Gunakan RT ini
                </button>
            </form>
        </div>
    </div>
@stop
