<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    /**
     * Mobile Authentication endpoint for Android Courier and Android Admin applications.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::with(['tenant', 'driver.fleet'])->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], 401);
        }

        // Generate session/api token representation for mobile client
        $apiToken = hash('sha256', Str::random(40) . $user->id . time());

        $driverData = null;
        if ($user->isDriver() && $user->driver) {
            $driverData = [
                'driver_id' => $user->driver->id,
                'license_number' => $user->driver->license_number,
                'is_online' => (bool) $user->driver->is_online,
                'is_on_duty' => (bool) $user->driver->is_on_duty,
                'last_lat' => (float) $user->driver->last_lat,
                'last_lng' => (float) $user->driver->last_lng,
                'fleet' => $user->driver->fleet ? [
                    'id' => $user->driver->fleet->id,
                    'vehicle_type' => $user->driver->fleet->vehicle_type,
                    'license_plate' => $user->driver->fleet->license_plate,
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $apiToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'tenant' => $user->tenant ? [
                    'id' => $user->tenant->id,
                    'name' => $user->tenant->name,
                    'slug' => $user->tenant->slug,
                ] : null,
                'driver' => $driverData,
            ],
            'client_target' => match ($user->role) {
                'DRIVER' => 'ANDROID_COURIER_APP',
                'SUPER_ADMIN', 'TENANT_ADMIN' => 'ANDROID_ADMIN_APP',
                'DISPATCHER' => 'DISPATCHER_PORTAL',
                default => 'CUSTOMER_PORTAL',
            },
        ]);
    }
}
