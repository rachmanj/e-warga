<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surat extends Model
{
    use BelongsToRt;

    protected $table = 'surat';

    protected $fillable = [
        'tenant_id',
        'surat_jenis_id',
        'keluarga_id',
        'warga_id',
        'nomor_urut',
        'tahun',
        'nomor_lengkap',
        'keperluan',
        'data_tambahan',
        'tanggal_ajuan',
        'tanggal_terbit',
        'status',
        'alasan_tolak',
        'dibuat_oleh',
        'disetujui_oleh',
        'disetujui_at',
        'file_path',
        'kode_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'data_tambahan' => 'array',
            'tanggal_ajuan' => 'date',
            'tanggal_terbit' => 'date',
            'disetujui_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Surat $surat): void {
            if ($surat->tenant_id === null) {
                $surat->tenant_id = ActiveRt::tenantScopeId();
            }
        });
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(SuratJenis::class, 'surat_jenis_id');
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function catatan(): HasMany
    {
        return $this->hasMany(SuratCatatan::class);
    }

    public function isTerbit(): bool
    {
        return $this->status === 'terbit';
    }
}
