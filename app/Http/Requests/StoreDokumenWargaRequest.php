<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDokumenWargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_dokumen') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(['ktp', 'kk', 'lainnya'])],
            'warga_id' => ['nullable', 'integer', 'exists:warga,id'],
            'berkas' => ['required', 'file', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis dokumen wajib dipilih.',
            'berkas.required' => 'Berkas wajib diunggah.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
        ];
    }
}
