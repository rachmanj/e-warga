<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKasTransaksiRequest extends FormRequest
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
            'tanggal' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'jenis' => ['required', Rule::in(['masuk', 'keluar'])],
            'pos' => ['required', Rule::in(['tunai', 'bank'])],
            'kas_kategori_id' => ['nullable', Rule::exists('kas_kategori', 'id')],
            'uraian' => ['required', 'string', 'max:500'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'no_bukti' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.before_or_equal' => 'Tanggal transaksi tidak boleh lebih dari satu hari di masa depan.',
            'pos.in' => 'Pos kas tidak valid.',
            'jumlah.gt' => 'Jumlah harus lebih besar dari nol.',
        ];
    }
}
