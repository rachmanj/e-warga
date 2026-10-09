<?php

namespace App\Http\Requests;

use App\Models\Keluarga;
use App\Models\SuratJenis;
use App\Models\Warga;
use App\Services\SuratService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_surat') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'surat_jenis_id' => [
                'required',
                Rule::exists('surat_jenis', 'id')->where('aktif', true),
            ],
            'keluarga_id' => ['nullable', Rule::exists('keluarga', 'id')],
            'warga_id' => ['nullable', Rule::exists('warga', 'id')],
            'keperluan' => ['nullable', 'string', 'max:5000'],
            'keperluan_sp' => ['nullable', 'array'],
            'keperluan_sp.*' => ['string', Rule::in(array_keys(SuratService::KEPERLUAN_SP))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $jenisId = $this->input('surat_jenis_id');
            if ($jenisId === null) {
                return;
            }

            $jenis = SuratJenis::query()->find($jenisId);
            if ($jenis === null) {
                return;
            }

            if ($jenis->kode === 'SP') {
                $terpilih = $this->input('keperluan_sp', []);
                if (! is_array($terpilih) || count(array_filter($terpilih)) < 1) {
                    $validator->errors()->add('keperluan_sp', 'Pilih minimal satu keperluan untuk Surat Pengantar.');
                }
            } elseif (! $this->filled('keperluan')) {
                $validator->errors()->add('keperluan', 'Keperluan surat wajib diisi.');
            }

            if ($jenis->butuh_data_warga) {
                if (! $this->filled('keluarga_id') || ! $this->filled('warga_id')) {
                    $validator->errors()->add('warga_id', 'Pemohon warga wajib dipilih untuk jenis surat ini.');
                }
            }

            $keluargaId = $this->input('keluarga_id');
            $wargaId = $this->input('warga_id');
            if ($keluargaId && $wargaId) {
                $warga = Warga::query()->where('keluarga_id', $keluargaId)->find($wargaId);
                if ($warga === null) {
                    $validator->errors()->add('warga_id', 'Anggota tidak termasuk keluarga yang dipilih.');
                }
            } elseif ($keluargaId) {
                Keluarga::query()->find($keluargaId);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'surat_jenis_id.required' => 'Jenis surat wajib dipilih.',
            'keperluan.required' => 'Keperluan surat wajib diisi.',
        ];
    }
}
