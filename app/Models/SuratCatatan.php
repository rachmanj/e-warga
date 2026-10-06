<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratCatatan extends Model
{
    protected $table = 'surat_catatan';

    protected $fillable = [
        'surat_id',
        'catatan',
        'oleh',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
