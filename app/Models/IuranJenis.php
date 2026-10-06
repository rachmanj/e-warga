<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IuranJenis extends Model
{
    use BelongsToRt;

    protected $table = 'iuran_jenis';

    protected $fillable = [
        'tenant_id',
        'nama',
        'nominal_default',
        'periode',
        'berlaku_dari',
        'berlaku_sampai',
        'aktif',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nominal_default' => 'decimal:2',
            'berlaku_dari' => 'date',
            'berlaku_sampai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (IuranJenis $jenis): void {
            if ($jenis->tenant_id === null) {
                $jenis->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function tarif(): HasMany
    {
        return $this->hasMany(IuranTarif::class);
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(IuranTagihan::class);
    }
}
