<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWargaMutasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_warga') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warga_id' => ['nullable', 'integer', 'exists:warga,id'],
            'jenis' => ['required', Rule::in(['masuk', 'keluar', 'lahir', 'meninggal', 'ubah_kk'])],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis mutasi wajib dipilih.',
            'tanggal.required' => 'Tanggal mutasi wajib diisi.',
        ];
    }
}
