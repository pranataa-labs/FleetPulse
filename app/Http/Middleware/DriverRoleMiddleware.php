<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Halaman ini khusus kurir. Silakan login dengan akun kurir.');
        }

        if (! $user->isDriver() && ! $user->isSuperAdmin()) {
            return redirect()->route('home')->with('error', 'Akses ditolak. Halaman ini hanya untuk kurir/driver.');
        }

        return $next($request);
    }
}
