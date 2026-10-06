<?php

namespace App\Http\Requests;

use App\Models\Keluarga;
use App\Support\ActiveRt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKeluargaRequest extends FormRequest
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
        return array_merge($this->keluargaRules(), $this->kepalaRules());
    }

    /**
     * @return array<string, mixed>
     */
    protected function keluargaRules(): array
    {
        return [
            'no_kk' => [
                'required',
                'regex:/^\d{16}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $tenantId = ActiveRt::tenantScopeId();
                    if ($tenantId === null) {
                        $fail('RT aktif tidak ditemukan.');

                        return;
                    }

                    $hash = hash_hmac('sha256', (string) $value, config('app.key'));
                    $exists = Keluarga::query()
                        ->where('tenant_id', $tenantId)
                        ->where('no_kk_hash', $hash)
                        ->exists();

                    if ($exists) {
                        $fail('Nomor KK sudah terdaftar di RT ini.');
                    }
                },
            ],
            'alamat' => ['required', 'string', 'max:500'],
            'blok_unit' => ['nullable', 'string', 'max:100'],
            'rt_lingkungan' => ['nullable', 'string', 'max:100'],
            'status_hunian' => ['required', Rule::in(['milik', 'sewa', 'kontrak', 'kos'])],
            'nama_pemilik' => ['nullable', 'string', 'max:255'],
            'tanggal_masuk' => ['nullable', 'date'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'status' => ['required', Rule::in(['aktif', 'pindah', 'nonaktif'])],
            'keterangan' => ['nullable', 'string'],
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'regex:/^\d{16}$/'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tanggal_lahir' => ['nullable', 'date'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'agama' => ['nullable', 'string', 'max:100'],
            'status_perkawinan' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function kepalaRules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_kk.required' => 'Nomor KK wajib diisi.',
            'no_kk.regex' => 'Nomor KK harus 16 digit angka.',
            'alamat.required' => 'Alamat wajib diisi.',
            'status_hunian.required' => 'Status hunian wajib dipilih.',
            'status_hunian.in' => 'Status hunian tidak valid.',
            'tanggal_keluar.after_or_equal' => 'Tanggal keluar tidak boleh lebih awal dari tanggal masuk.',
            'nama.required' => 'Nama kepala keluarga wajib diisi.',
            'nik.regex' => 'NIK harus 16 digit angka.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
        ];
    }
}
