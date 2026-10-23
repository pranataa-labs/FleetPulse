<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Real-Time Customer Order Tracking Channel (Public token)
Broadcast::channel('order.{token}', function () {
    return true;
});

// Tenant Dispatch Operations Channel
Broadcast::channel('tenant.{tenantId}.dispatch', function ($user, $tenantId) {
    return (int) $user->tenant_id === (int) $tenantId || $user->isSuperAdmin();
});
