<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KasKategori extends Model
{
    use BelongsToRt;

    protected $table = 'kas_kategori';

    protected $fillable = [
        'tenant_id',
        'nama',
        'jenis',
    ];

    protected static function booted(): void
    {
        static::creating(function (KasKategori $kategori): void {
            if ($kategori->tenant_id === null) {
                $kategori->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class);
    }
}
