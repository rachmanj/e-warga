<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratJenis extends Model
{
    use BelongsToRt;

    protected $table = 'surat_jenis';

    protected $fillable = [
        'tenant_id',
        'kode',
        'nama',
        'format_nomor',
        'template_body',
        'butuh_data_warga',
        'aktif',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'butuh_data_warga' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SuratJenis $jenis): void {
            if ($jenis->tenant_id === null) {
                $jenis->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
