<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIuranJenisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_iuran') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nominal_default' => ['required', 'numeric', 'gt:0'],
            'periode' => ['required', 'in:bulanan,tahunan'],
            'aktif' => ['sometimes', 'boolean'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jenis iuran wajib diisi.',
            'nominal_default.required' => 'Nominal default wajib diisi.',
            'nominal_default.gt' => 'Nominal default harus lebih besar dari nol.',
        ];
    }
}
