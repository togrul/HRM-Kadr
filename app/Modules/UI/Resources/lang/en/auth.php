<?php

return [
    'titles' => [
        'app_name' => 'Human Resources Management System',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Password confirmation',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'confirm_password' => 'Confirm password',
        'remember_me' => 'Remember me',
    ],
    'actions' => [
        'log_in' => 'Log in',
        'register' => 'Register',
        'reset_password' => 'Reset password',
        'email_password_reset_link' => 'Email password reset link',
        'resend_verification_email' => 'Resend verification email',
        'log_out' => 'Log out',
        'confirm' => 'Confirm',
    ],
    'links' => [
        'forgot_password' => 'Forgot your password?',
        'already_registered' => 'Already registered?',
    ],
    'messages' => [
        'verification_pending' => 'Before getting started, please verify your email address by clicking the link we just emailed to you. If you did not receive the email, we will gladly send you another.',
        'verification_link_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'secure_area_confirmation' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'forgot_password_help' => 'No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
        'email_unverified' => 'Your email address is unverified.',
        'resend_verification_prompt' => 'Click here to re-send the verification email.',
        'new_verification_link_sent' => 'A new verification link has been sent to your email address.',
    ],
];
