<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IuranPembayaran extends Model
{
    use BelongsToRt;

    protected $table = 'iuran_pembayaran';

    protected $fillable = [
        'tenant_id',
        'iuran_tagihan_id',
        'tanggal',
        'jumlah',
        'metode',
        'no_referensi',
        'bukti_path',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (IuranPembayaran $pembayaran): void {
            if ($pembayaran->tenant_id === null) {
                $pembayaran->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(IuranTagihan::class, 'iuran_tagihan_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class);
    }

    public function kasTransaksi(): HasOne
    {
        return $this->hasOne(KasTransaksi::class);
    }
}
