<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Yalnız aktiv hesaba link göndərilir. Cavab HƏR HALDA eynidir — e-poçtun sistemdə
        // olub-olmadığı, hesabın deaktiv olduğu və ya limitə düşdüyü bildirilmir.
        Password::sendResetLink([
            'email' => (string) $request->input('email'),
            'is_active' => true,
        ]);

        return back()->with('status', __('passwords.sent_generic'));
    }
}
