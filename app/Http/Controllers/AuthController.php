<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        $demoUsers = User::with('tenant')->take(6)->get();
        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->isSuperAdmin() || $user->isTenantAdmin()) {
                $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');
                return redirect()->to("/{$adminPrefix}/dashboard");
            }

            if ($user->isDispatcher()) {
                return redirect()->route('dispatcher.index');
            }

            if ($user->isDriver()) {
                return redirect()->route('driver.index');
            }

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function switchDemo(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        Auth::login($user);

        if ($user->isSuperAdmin() || $user->isTenantAdmin()) {
            $adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');
            return redirect()->to("/{$adminPrefix}/dashboard")->with('success', "Logged in as {$user->name} ({$user->role})");
        }

        if ($user->isDispatcher()) {
            return redirect()->route('dispatcher.index')->with('success', "Logged in as {$user->name} ({$user->role})");
        }

        if ($user->isDriver()) {
            return redirect()->route('driver.index', ['driver_id' => $user->driver?->id])->with('success', "Logged in as {$user->name} ({$user->role})");
        }

        return redirect()->route('home')->with('success', "Logged in as {$user->name} ({$user->role})");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
