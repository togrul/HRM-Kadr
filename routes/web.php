<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Orders\AllOrders;
use App\Livewire\PersonalAffairs\AllAffairs;
use App\Livewire\Personnel\AllPersonnel;
use App\Livewire\Services\Service;
use App\Livewire\StaffSchedule\Staffs;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

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
    Route::get('/', AllPersonnel::class)->name('home');
    Route::get('/dashboard', AllAffairs::class)->name('dashboard');
    Route::get('/staffs', Staffs::class)->name('staffs');
    Route::get('/services', Service::class)->name('services');
    Route::get('/orders', AllOrders::class)->name('orders');
    Route::get('/candidates', \App\Livewire\Candidates\CandidateList::class)->name('candidates');
    Route::get('/vacations', \App\Livewire\Vacation\Vacations::class)->name('vacations.list');
    Route::get('/business-trips', \App\Livewire\Outside\BusinessTrips::class)->name('business-trips.list');

    Route::prefix('/profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'edit')->name('profile.edit');
        Route::patch('/', 'update')->name('profile.update');
        Route::delete('/', 'destroy')->name('profile.destroy');
    });

    Route::prefix('print')->controller(\App\Http\Controllers\PrintController::class)->group(function () {
        Route::get('personnel/{id?}', 'personnel_service_book')->name('print.personnel');
        Route::get('page/{model?}', 'print_page')->name('print.page');
    });

    // Livewire::setUpdateRoute(function ($handle) {
    //     return Route::post('/livewire/update', $handle);
    // });
});

require __DIR__.'/auth.php';
