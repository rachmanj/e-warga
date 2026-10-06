<?php

namespace App\Http\Middleware;

use App\Models\Rt;
use App\Models\User;
use App\Support\ActiveRt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRt
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            if ($request->routeIs('rt-aktif.*')) {
                return $next($request);
            }

            if (! session(ActiveRt::SESSION_KEY)) {
                return redirect()->route('rt-aktif.index');
            }

            $rt = Rt::query()->find(session(ActiveRt::SESSION_KEY));

            if ($rt === null || ! $rt->publik_aktif) {
                session()->forget(ActiveRt::SESSION_KEY);

                return redirect()->route('rt-aktif.index');
            }

            return $next($request);
        }

        if ($user->tenant_id === null) {
            abort(403, 'Akun tidak terhubung ke RT.');
        }

        $rt = Rt::query()->find($user->tenant_id);

        if ($rt === null || ! $rt->publik_aktif) {
            abort(403, 'RT tidak aktif.');
        }

        return $next($request);
    }
}
