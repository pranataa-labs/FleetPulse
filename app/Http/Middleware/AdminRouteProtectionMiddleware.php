<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminRouteProtectionMiddleware
{
    /**
     * Handle an incoming request for admin routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Unauthenticated
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // 2. Role Check: Must be SUPERADMIN or TENANT_ADMIN
        if (! $user->isSuperAdmin() && ! $user->isTenantAdmin()) {
            Log::warning('Unauthorized admin route access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Anda bukan admin.'], 403);
            }

            return redirect()->route('home')->with('error', 'Akses ditolak. Anda bukan admin.');
        }

        return $next($request);
    }
}
