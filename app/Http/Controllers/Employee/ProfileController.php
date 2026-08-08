<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $user->load(['activeSchedule.shift', 'activeQrCode']);
        $editable = User::employeeEditableFields();

        return view('employee.profile', compact('user', 'editable'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $editable = User::employeeEditableFields();
        $allowed = array_intersect($editable, ['phone', 'profile_picture']);

        $rules = [];
        if (in_array('phone', $allowed, true)) {
            $rules['phone'] = ['nullable', 'string', 'max:50'];
        }
        if (in_array('profile_picture', $allowed, true)) {
            $rules['profile_picture'] = ['nullable', 'image', 'max:2048'];
        }

        if ($rules === []) {
            return back()->with('error', 'No profile fields are currently editable. Contact an administrator.');
        }

        $data = $request->validate($rules);
        $payload = [];

        if (array_key_exists('phone', $data)) {
            $payload['phone'] = $data['phone'];
        }

        if ($request->hasFile('profile_picture') && in_array('profile_picture', $allowed, true)) {
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $payload['profile_picture'] = $path;
        }

        if ($payload !== []) {
            $user->update($payload);
        }

        return redirect()
            ->route('employee.profile')
            ->with('success', 'Profile updated.');
    }
}
