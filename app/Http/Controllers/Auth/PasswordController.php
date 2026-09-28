<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $wasForced = (bool) ($user->getAttributes()['must_reset_password'] ?? false);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_reset_password' => false,
        ])->save();

        // A forced reset lands back on a clean profile page (no reset banner), free to go anywhere.
        if ($wasForced) {
            return redirect()->route('profile.edit')->with('status', 'password-updated');
        }

        return back()->with('status', 'password-updated');
    }
}
