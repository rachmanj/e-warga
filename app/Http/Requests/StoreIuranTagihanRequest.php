<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIuranTagihanRequest extends FormRequest
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
            'iuran_jenis_id' => ['required', Rule::exists('iuran_jenis', 'id')],
            'periode' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'jatuh_tempo' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'iuran_jenis_id.required' => 'Jenis iuran wajib dipilih.',
            'periode.required' => 'Periode wajib diisi (format YYYY-MM).',
            'periode.regex' => 'Format periode harus YYYY-MM.',
        ];
    }
}
