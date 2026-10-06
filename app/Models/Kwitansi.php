<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kwitansi extends Model
{
    use BelongsToRt;

    protected $table = 'kwitansi';

    protected $fillable = [
        'tenant_id',
        'iuran_pembayaran_id',
        'nomor',
        'tahun',
        'tanggal',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Kwitansi $kwitansi): void {
            if ($kwitansi->tenant_id === null) {
                $kwitansi->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(IuranPembayaran::class, 'iuran_pembayaran_id');
    }
}
