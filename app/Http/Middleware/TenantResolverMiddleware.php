<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TenantResolverMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            TenantContext::setTenant($tenant);
            app()->instance('current_tenant', $tenant);
            view()->share('currentTenant', $tenant);
        }

        $response = $next($request);

        return $response;
    }

    /**
     * Resolve tenant from request parameters, headers, or subdomains.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        if (! Schema::hasTable('tenants')) {
            return null;
        }

        // 1. Authenticated user tenant
        if ($user = $request->user()) {
            if ($user->tenant_id) {
                return Tenant::find($user->tenant_id);
            }
        }

        // 2. Custom header
        if ($tenantId = $request->header('X-Tenant-ID')) {
            return Tenant::find($tenantId);
        }

        if ($slug = $request->header('X-Tenant-Slug')) {
            return Tenant::where('slug', $slug)->first();
        }

        // 3. Query string parameter or session
        if ($slug = $request->query('tenant')) {
            $tenant = Tenant::where('slug', $slug)->first();
            if ($tenant) {
                session(['active_tenant_slug' => $slug]);
                return $tenant;
            }
        }

        if ($sessionSlug = session('active_tenant_slug')) {
            $tenant = Tenant::where('slug', $sessionSlug)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // 4. Subdomain resolution (e.g. swiftexpress.fleetpulse.test)
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            $tenant = Tenant::where('slug', $subdomain)->orWhere('domain', $host)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Fallback default for development if only 1 or 2 tenants exist and none specified
        return Tenant::first();
    }
}
