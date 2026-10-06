<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRt;
use App\Models\Concerns\HashesSensitiveIdentifiers;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warga extends Model
{
    use BelongsToRt;
    use HashesSensitiveIdentifiers;

    protected $table = 'warga';

    protected $fillable = [
        'tenant_id',
        'keluarga_id',
        'nik',
        'nik_hash',
        'nik_4_terakhir',
        'nama',
        'hubungan',
        'jenis_kelamin',
        'tanggal_lahir',
        'pekerjaan',
        'agama',
        'status_perkawinan',
        'no_hp',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'tanggal_lahir' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Warga $warga): void {
            if ($warga->tenant_id === null) {
                $warga->tenant_id = ActiveRt::tenantScopeId();
            }
        });

        static::saving(function (Warga $warga): void {
            if ($warga->isDirty('nik')) {
                if ($warga->nik !== null && $warga->nik !== '') {
                    $warga->nik_hash = static::identifierHmac($warga->nik);
                    $warga->nik_4_terakhir = substr($warga->nik, -4);
                } else {
                    $warga->nik_hash = null;
                    $warga->nik_4_terakhir = null;
                }
            }
        });
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(WargaMutasi::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenWarga::class);
    }

    protected function nikTersamar(): Attribute
    {
        return Attribute::get(fn (): ?string => static::maskSixteenDigitIdentifier($this->nik));
    }
}
