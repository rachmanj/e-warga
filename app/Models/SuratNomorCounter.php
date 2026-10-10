<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratNomorCounter extends Model
{
    protected $table = 'surat_nomor_counter';

    protected $fillable = [
        'tenant_id',
        'tahun',
        'nomor_terakhir',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'nomor_terakhir' => 'integer',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'tenant_id');
    }
}
