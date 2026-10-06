<?php

namespace App\Support;

use App\Models\Rt;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActiveRt
{
    public const SESSION_KEY = 'rt_aktif_id';

    public static function tenantScopeId(): ?int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $id = session(self::SESSION_KEY);

            return $id ? (int) $id : null;
        }

        return $user->tenant_id;
    }

    public static function current(): ?Rt
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $id = session(self::SESSION_KEY);

            return $id ? Rt::query()->find($id) : null;
        }

        if ($user->tenant_id === null) {
            return null;
        }

        return Rt::query()->find($user->tenant_id);
    }

    public static function setForSuperAdmin(int $rtId): void
    {
        session([self::SESSION_KEY => $rtId]);
    }
}
