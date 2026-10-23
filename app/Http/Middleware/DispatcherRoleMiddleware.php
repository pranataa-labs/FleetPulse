<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatcherRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Halaman ini khusus tim operasional dispatcher. Silakan login terlebih dahulu.');
        }

        if (! $user->isDispatcher() && ! $user->isTenantAdmin() && ! $user->isSuperAdmin()) {
            return redirect()->route('home')->with('error', 'Akses ditolak. Anda bukan dispatcher atau admin.');
        }

        return $next($request);
    }
}
