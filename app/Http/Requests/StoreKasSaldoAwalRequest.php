<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKasSaldoAwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_kas') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'pos' => ['required', Rule::in(['tunai', 'bank'])],
            'jumlah' => ['required', 'numeric', 'gte:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun wajib dipilih.',
            'pos.required' => 'Pos kas wajib dipilih.',
            'pos.in' => 'Pos kas tidak valid.',
        ];
    }
}
