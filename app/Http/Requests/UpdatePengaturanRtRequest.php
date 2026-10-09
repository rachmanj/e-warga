<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengaturanRtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_rt') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'nama_ketua',
            'nama_sekretaris',
            'nama_bendahara',
            'alamat_sekretariat',
            'kode_pos',
            'no_hp',
            'rw',
            'kelurahan',
            'kecamatan',
            'kota',
        ];

        $merge = [];
        foreach ($nullable as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'nama_ketua' => ['nullable', 'string', 'max:255'],
            'nama_sekretaris' => ['nullable', 'string', 'max:255'],
            'nama_bendahara' => ['nullable', 'string', 'max:255'],
            'alamat_sekretariat' => ['nullable', 'string', 'max:1000'],
            'kode_pos' => ['nullable', 'regex:/^\d{5}$/'],
            'no_hp' => ['nullable', 'regex:/^[\d\s\-\+]{8,20}$/'],
            'rw' => ['nullable', 'string', 'max:50'],
            'kelurahan' => ['nullable', 'string', 'max:255'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama RT wajib diisi.',
            'nama.max' => 'Nama RT maksimal 100 karakter.',
            'kode_pos.regex' => 'Kode pos harus 5 digit angka.',
            'no_hp.regex' => 'Nomor HP harus 8–20 karakter dan hanya boleh berisi angka, spasi, tanda hubung, atau tanda tambah.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama RT',
            'nama_ketua' => 'nama ketua',
            'nama_sekretaris' => 'nama sekretaris',
            'nama_bendahara' => 'nama bendahara',
            'alamat_sekretariat' => 'alamat sekretariat',
            'kode_pos' => 'kode pos',
            'no_hp' => 'nomor HP',
            'rw' => 'RW',
            'kelurahan' => 'kelurahan',
            'kecamatan' => 'kecamatan',
            'kota' => 'kota',
        ];
    }
}
