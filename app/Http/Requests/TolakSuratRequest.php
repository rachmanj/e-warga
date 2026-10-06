<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TolakSuratRequest extends FormRequest
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
            'alasan_tolak' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan_tolak.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
