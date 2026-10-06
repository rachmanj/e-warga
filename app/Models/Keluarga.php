<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Models\Concerns\HashesSensitiveIdentifiers;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keluarga extends Model
{
    use BelongsToRt;
    use HashesSensitiveIdentifiers;

    protected $table = 'keluarga';

    protected $fillable = [
        'tenant_id',
        'no_kk',
        'no_kk_hash',
        'alamat',
        'blok_unit',
        'rt_lingkungan',
        'status_hunian',
        'nama_pemilik',
        'tanggal_masuk',
        'tanggal_keluar',
        'status',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'no_kk' => 'encrypted',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Keluarga $keluarga): void {
            if ($keluarga->tenant_id === null) {
                $keluarga->tenant_id = ActiveRt::tenantScopeId();
            }
        });

        static::saving(function (Keluarga $keluarga): void {
            if ($keluarga->isDirty('no_kk') && $keluarga->no_kk !== null && $keluarga->no_kk !== '') {
                $keluarga->no_kk_hash = static::identifierHmac($keluarga->no_kk);
            }
        });
    }

    public function warga(): HasMany
    {
        return $this->hasMany(Warga::class);
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(WargaMutasi::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenWarga::class);
    }

    public function kepalaKeluarga(): ?Warga
    {
        return $this->warga()->where('hubungan', 'kepala')->first();
    }

    protected function noKkTersamar(): Attribute
    {
        return Attribute::get(fn (): ?string => static::maskSixteenDigitIdentifier($this->no_kk));
    }
}
