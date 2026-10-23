<?php

namespace App\Services;

use App\Models\Tenant;

class TenantContext
{
    protected static ?Tenant $currentTenant = null;

    public static function setTenant(?Tenant $tenant): void
    {
        static::$currentTenant = $tenant;
    }

    public static function getTenant(): ?Tenant
    {
        return static::$currentTenant;
    }

    public static function getTenantId(): ?int
    {
        return static::$currentTenant?->id;
    }

    public static function clear(): void
    {
        static::$currentTenant = null;
    }
}
