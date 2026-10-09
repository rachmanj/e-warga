<?php

namespace App\Http\Requests;

use App\Models\Keluarga;
use App\Support\ActiveRt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKeluargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kelola_warga') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('no_kk') && $this->input('no_kk') === '') {
            $this->merge(['no_kk' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keluarga = $this->route('keluarga');
        $keluargaId = $keluarga instanceof Keluarga ? $keluarga->id : null;

        return [
            'no_kk' => [
                'nullable',
                'regex:/^\d{16}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($keluargaId): void {
                    if ($value === null) {
                        return;
                    }

                    $tenantId = ActiveRt::tenantScopeId();
                    if ($tenantId === null) {
                        $fail('RT aktif tidak ditemukan.');

                        return;
                    }

                    $hash = hash_hmac('sha256', (string) $value, config('app.key'));
                    $exists = Keluarga::query()
                        ->where('tenant_id', $tenantId)
                        ->where('no_kk_hash', $hash)
                        ->when($keluargaId, fn ($q) => $q->where('id', '!=', $keluargaId))
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_kk.regex' => 'Nomor KK harus 16 digit angka.',
            'alamat.required' => 'Alamat wajib diisi.',
            'status_hunian.required' => 'Status hunian wajib dipilih.',
            'tanggal_keluar.after_or_equal' => 'Tanggal keluar tidak boleh lebih awal dari tanggal masuk.',
        ];
    }
}
