<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rt extends Model
{
    protected $fillable = [
        'nama',
        'rw',
        'kelurahan',
        'kecamatan',
        'kota',
        'kode_pos',
        'nama_ketua',
        'nama_sekretaris',
        'nama_bendahara',
        'alamat_sekretariat',
        'no_hp',
        'slug',
        'publik_aktif',
    ];

    protected function casts(): array
    {
        return [
            'publik_aktif' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function displayName(): string
    {
        return $this->nama;
    }
}
