<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\ActiveRt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToRt
{
    public static function bootBelongsToRt(): void
    {
        if (static::class === User::class) {
            return;
        }

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = ActiveRt::tenantScopeId();

            if ($tenantId === null) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
        });
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Rt::class, 'tenant_id');
    }
}
