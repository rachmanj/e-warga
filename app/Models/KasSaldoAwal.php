<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;

class KasSaldoAwal extends Model
{
    use BelongsToRt;

    protected $table = 'kas_saldo_awal';

    protected $fillable = [
        'tenant_id',
        'tahun',
        'pos',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KasSaldoAwal $saldo): void {
            if ($saldo->tenant_id === null) {
                $saldo->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }
}
