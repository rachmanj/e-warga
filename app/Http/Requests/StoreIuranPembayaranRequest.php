<?php

namespace App\Http\Requests;

use App\Models\IuranTagihan;
use App\Services\IuranService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreIuranPembayaranRequest extends FormRequest
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
            'tanggal' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'metode' => ['required', 'in:tunai,transfer,lainnya'],
            'no_referensi' => ['nullable', 'string', 'max:100'],
            'bukti' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jumlah.required' => 'Jumlah pembayaran wajib diisi.',
            'jumlah.gt' => 'Jumlah pembayaran harus lebih besar dari nol.',
            'tanggal.before_or_equal' => 'Tanggal transaksi tidak boleh lebih dari satu hari di masa depan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tagihan = $this->route('tagihan');
            if (! $tagihan instanceof IuranTagihan) {
                return;
            }

            $jumlah = number_format((float) $this->input('jumlah'), 2, '.', '');
            $sisa = app(IuranService::class)->tagihanEfektif($tagihan)['sisa'];

            if (bccomp($jumlah, $sisa, 2) > 0) {
                $validator->errors()->add('jumlah', 'Jumlah pembayaran tidak boleh melebihi sisa tagihan.');
            }
        });
    }
}
