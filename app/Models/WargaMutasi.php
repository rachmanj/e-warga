<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WargaMutasi extends Model
{
    use BelongsToRt;

    protected $table = 'warga_mutasi';

    protected $fillable = [
        'tenant_id',
        'keluarga_id',
        'warga_id',
        'jenis',
        'tanggal',
        'keterangan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WargaMutasi $mutasi): void {
            if ($mutasi->tenant_id === null) {
                $mutasi->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
