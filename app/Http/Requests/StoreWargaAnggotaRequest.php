<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWargaAnggotaRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'regex:/^\d{16}$/'],
            'hubungan' => ['required', Rule::in(['kepala', 'istri', 'anak', 'famili', 'lain'])],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tanggal_lahir' => ['nullable', 'date'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'agama' => ['nullable', 'string', 'max:100'],
            'status_perkawinan' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['aktif', 'pindah', 'meninggal'])],
            'catatan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nik.regex' => 'NIK harus 16 digit angka.',
            'hubungan.required' => 'Hubungan keluarga wajib dipilih.',
            'hubungan.in' => 'Hubungan keluarga tidak valid.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
        ];
    }
}
