<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IuranTarif extends Model
{
    use BelongsToRt;

    protected $table = 'iuran_tarif';

    protected $fillable = [
        'tenant_id',
        'iuran_jenis_id',
        'keluarga_id',
        'nominal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (IuranTarif $tarif): void {
            if ($tarif->tenant_id === null) {
                $tarif->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(IuranJenis::class, 'iuran_jenis_id');
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }
}
