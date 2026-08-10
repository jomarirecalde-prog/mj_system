<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeLoginController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }

    public function showForgotPassword(): View
    {
        return view('employee.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($data['identifier']);
        $throttleKey = 'employee-reset|'.Str::lower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'identifier' => "Please wait {$seconds} seconds before requesting another reset link.",
            ]);
        }

        $user = User::query()
            ->where('role', 'employee')
            ->where('status', 'active')
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                    ->orWhere('employee_id', $identifier)
                    ->orWhereRaw('UPPER(employee_id) = ?', [Str::upper($identifier)]);
            })
            ->first();

        RateLimiter::hit($throttleKey, 60);

        if ($user === null) {
            // Do not reveal whether the account exists.
            return back()->with('success', 'If an active employee account matches that ID or email, a reset link has been sent.');
        }

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'If an active employee account matches that ID or email, a reset link has been sent.')
            : back()->withErrors(['identifier' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('employee.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password): void {
                if (! $user->isEmployee()) {
                    return;
                }

                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password updated. You can sign in now.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}
