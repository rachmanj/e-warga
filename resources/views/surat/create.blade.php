@extends('adminlte::page')

@section('title', 'Ajukan Surat')

@section('content_header')
    <h1>Ajukan Surat</h1>
@stop

@section('content')
    <div class="card card-outline card-success">
        <div class="card-body">
            <form method="post" action="{{ route('surat.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Jenis surat <span class="text-danger">*</span></label>
                    <select name="surat_jenis_id" id="surat_jenis_id" class="form-select @error('surat_jenis_id') is-invalid @enderror" required>
                        <option value="">— Pilih jenis —</option>
                        @foreach ($jenisList as $jenis)
                            <option value="{{ $jenis->id }}" data-butuh-warga="{{ $jenis->butuh_data_warga ? '1' : '0' }}" data-kode="{{ $jenis->kode }}" @selected(old('surat_jenis_id') == $jenis->id)>
                                {{ $jenis->kode }} — {{ $jenis->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('surat_jenis_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div id="blok-warga">
                    <div class="mb-3">
                        <label class="form-label">Keluarga</label>
                        <select name="keluarga_id" id="keluarga_id" class="form-select @error('keluarga_id') is-invalid @enderror">
                            <option value="">— Pilih keluarga —</option>
                            @foreach ($keluarga as $kk)
                                <option value="{{ $kk->id }}" @selected(old('keluarga_id') == $kk->id)>{{ $kk->alamat }} ({{ $kk->warga->count() }} anggota)</option>
                            @endforeach
                        </select>
                        @error('keluarga_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pemohon (warga)</label>
                        <select name="warga_id" id="warga_id" class="form-select @error('warga_id') is-invalid @enderror">
                            <option value="">— Pilih pemohon —</option>
                        </select>
                        @error('warga_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3" id="blok-keperluan-teks">
                    <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                    <textarea name="keperluan" id="keperluan_teks" rows="4" class="form-control @error('keperluan') is-invalid @enderror">{{ old('keperluan') }}</textarea>
                    @error('keperluan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3 d-none" id="blok-keperluan-sp">
                    <label class="form-label d-block">Keperluan untuk mengurus <span class="text-danger">*</span></label>
                    @php $oldKeperluanSp = old('keperluan_sp', []); @endphp
                    @foreach ($keperluanSp as $kode => $label)
                        <div class="form-check">
                            <input class="form-check-input @error('keperluan_sp') is-invalid @enderror" type="checkbox" name="keperluan_sp[]" value="{{ $kode }}" id="keperluan_sp_{{ $kode }}" @checked(in_array($kode, $oldKeperluanSp, true))>
                            <label class="form-check-label" for="keperluan_sp_{{ $kode }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    @error('keperluan_sp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn text-white" style="background-color: #059669; border-color: #059669;">Simpan pengajuan</button>
                    <a href="{{ route('surat.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
@stop

@push('js')
<script>
    const anggotaPerKeluarga = @json($anggotaPerKeluarga);

    function muatAnggota() {
        const keluargaId = document.getElementById('keluarga_id').value;
        const select = document.getElementById('warga_id');
        select.innerHTML = '<option value="">— Pilih pemohon —</option>';
        const list = anggotaPerKeluarga[keluargaId] || [];
        list.forEach(function (w) {
            const opt = document.createElement('option');
            opt.value = w.id;
            opt.textContent = w.nama;
            select.appendChild(opt);
        });
        @if (old('warga_id'))
        select.value = @json(old('warga_id'));
        @endif
    }

    function sesuaikanFormJenis() {
        const select = document.getElementById('surat_jenis_id');
        const option = select.options[select.selectedIndex];
        const kode = option ? option.getAttribute('data-kode') : '';
        const isSp = kode === 'SP';
        const blokTeks = document.getElementById('blok-keperluan-teks');
        const blokSp = document.getElementById('blok-keperluan-sp');
        const textarea = document.getElementById('keperluan_teks');

        if (isSp) {
            blokTeks.classList.add('d-none');
            blokSp.classList.remove('d-none');
            textarea.removeAttribute('required');
        } else {
            blokTeks.classList.remove('d-none');
            blokSp.classList.add('d-none');
            textarea.setAttribute('required', 'required');
        }
    }

    document.getElementById('keluarga_id').addEventListener('change', muatAnggota);
    document.getElementById('surat_jenis_id').addEventListener('change', sesuaikanFormJenis);
    muatAnggota();
    sesuaikanFormJenis();
</script>
@endpush
