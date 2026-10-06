<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenWarga extends Model
{
    use BelongsToRt;

    protected $table = 'dokumen_warga';

    protected $fillable = [
        'tenant_id',
        'keluarga_id',
        'warga_id',
        'jenis',
        'nama_asli',
        'file_path',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (DokumenWarga $dokumen): void {
            if ($dokumen->tenant_id === null) {
                $dokumen->tenant_id = ActiveRt::tenantScopeId();
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

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
