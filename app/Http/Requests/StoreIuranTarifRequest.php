<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIuranTarifRequest extends FormRequest
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
            'keluarga_id' => ['required', Rule::exists('keluarga', 'id')],
            'nominal' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keluarga_id.required' => 'Keluarga wajib dipilih.',
            'nominal.required' => 'Nominal tarif wajib diisi.',
            'nominal.gt' => 'Nominal tarif harus lebih besar dari nol.',
        ];
    }
}
