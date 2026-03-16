<?php

use App\Http\Controllers\TrainingPerformanceGuideController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Services\Service;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::prefix('/profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'edit')->name('profile.edit');
        Route::patch('/', 'update')->name('profile.update');
        Route::delete('/', 'destroy')->name('profile.destroy');
    });

    Route::get('/docs', TrainingPerformanceGuideController::class)
        ->name('docs.guide');
    Route::get('/docs/sections/{module}', [TrainingPerformanceGuideController::class, 'section'])
        ->whereIn('module', ['training', 'performance', 'attendance', 'orders'])
        ->name('docs.section');
});

require __DIR__ . '/auth.php';
