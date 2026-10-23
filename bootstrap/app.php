<?php

use App\Http\Middleware\AdminRouteProtectionMiddleware;
use App\Http\Middleware\TenantResolverMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            TenantResolverMiddleware::class,
        ]);

        $middleware->api(append: [
            TenantResolverMiddleware::class,
        ]);

        $middleware->alias([
            'tenant' => TenantResolverMiddleware::class,
            'admin.protect' => AdminRouteProtectionMiddleware::class,
            'role.driver' => \App\Http\Middleware\DriverRoleMiddleware::class,
            'role.dispatcher' => \App\Http\Middleware\DispatcherRoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
