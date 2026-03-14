<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class AttendanceGuideController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->to(route('training-performance.user-guide', ['focus' => 'attendance']) . '#attendance-module');
    }
}
