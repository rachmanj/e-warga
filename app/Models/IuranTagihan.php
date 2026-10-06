<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IuranTagihan extends Model
{
    use BelongsToRt;

    protected $table = 'iuran_tagihan';

    protected $fillable = [
        'tenant_id',
        'keluarga_id',
        'iuran_jenis_id',
        'periode',
        'nominal',
        'jatuh_tempo',
        'status',
        'alasan_bebas',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'jatuh_tempo' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (IuranTagihan $tagihan): void {
            if ($tagihan->tenant_id === null) {
                $tagihan->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(IuranJenis::class, 'iuran_jenis_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(IuranPembayaran::class);
    }
}
