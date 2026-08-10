<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        $base = InventoryItem::query()->active();

        $landingStats = [
            'products' => (clone $base)->count(),
            'stock' => (float) (clone $base)->sum('quantity'),
            'low_stock' => (clone $base)
                ->where('inventory_type', InventoryItem::TYPE_CONSUMABLE)
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->where('reorder_level', '>', 0)
                ->count(),
            'transactions' => InventoryTransaction::query()->count(),
        ];

        return view('auth.login', compact('landingStats'));
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $user = Auth::user();

        if ($user !== null && ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Your account is inactive. Please contact an administrator.']);
        }

        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now('Asia/Manila')->timestamp);

        if ($user !== null) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        if ($user !== null && $user->isEmployee()) {
            return redirect()->intended(route('employee.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
