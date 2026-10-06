<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasTransaksi extends Model
{
    use BelongsToRt;

    protected $table = 'kas_transaksi';

    protected $fillable = [
        'tenant_id',
        'tanggal',
        'jenis',
        'pos',
        'kas_kategori_id',
        'uraian',
        'jumlah',
        'no_bukti',
        'iuran_pembayaran_id',
        'created_by',
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
        static::creating(function (KasTransaksi $transaksi): void {
            if ($transaksi->tenant_id === null) {
                $transaksi->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KasKategori::class, 'kas_kategori_id');
    }

    public function iuranPembayaran(): BelongsTo
    {
        return $this->belongsTo(IuranPembayaran::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
