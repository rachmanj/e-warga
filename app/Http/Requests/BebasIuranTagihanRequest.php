<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BebasIuranTagihanRequest extends FormRequest
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
            'alasan' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pembebasan wajib diisi.',
        ];
    }
}
