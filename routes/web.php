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
    Route::get('/staffs', Staffs::class)->name('staffs');
    Route::get('/services', Service::class)->name('services');
    Route::get('/orders', AllOrders::class)->name('orders');
    Route::get('/candidates', \App\Livewire\Candidates\CandidateList::class)->name('candidates');
    Route::get('/vacations', \App\Livewire\Vacation\Vacations::class)->name('vacations.list');
    Route::get('/business-trips', \App\Livewire\Outside\BusinessTrips::class)->name('business-trips.list');
    Route::get('/notifications', \App\Livewire\Notification\NotificationList::class)->name('notifications');

    Route::prefix('/profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'edit')->name('profile.edit');
        Route::patch('/', 'update')->name('profile.update');
        Route::delete('/', 'destroy')->name('profile.destroy');
    });

    Route::prefix('print')->controller(\App\Http\Controllers\PrintController::class)->group(function () {
        Route::get('personnel/{id?}', 'personnel_service_book')->name('print.personnel');
        Route::get('page/{model?}', 'print_page')->name('print.page');
    });

    Route::middleware(['can:access-admin'])->prefix('/admin')->group(function () {
        Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('admin');
        Route::get('/appeal-statuses', \App\Livewire\Admin\AppealStatus::class)->name('admin.appeal-status');
        Route::get('/awards', \App\Livewire\Admin\Awards::class)->name('admin.awards');
        Route::get('/cities', \App\Livewire\Admin\Cities::class)->name('admin.cities');
        Route::get('/countries', \App\Livewire\Admin\Countries::class)->name('admin.countries');
        Route::get('/education-degrees', \App\Livewire\Admin\EducationDegrees::class)->name('admin.education-degrees');
        Route::get('/document-types', \App\Livewire\Admin\DocumentTypes::class)->name('admin.document-types');
        Route::get('/education-forms', \App\Livewire\Admin\EducationForms::class)->name('admin.education-forms');
        Route::get('/education-types', \App\Livewire\Admin\EducationTypes::class)->name('admin.education-types');
        Route::get('/educational-institutions', \App\Livewire\Admin\EducationalInstitutions::class)->name('admin.educational-institutions');
        Route::get('/kinship', \App\Livewire\Admin\Kinships::class)->name('admin.kinship');
        Route::get('/order-categories', \App\Livewire\Admin\OrderCategories::class)->name('admin.order-categories');
        Route::get('/order-statuses', \App\Livewire\Admin\OrderStatuses::class)->name('admin.order-statuses');
        Route::get('/positions', \App\Livewire\Admin\Positions::class)->name('admin.positions');
        Route::get('/structures', \App\Livewire\Admin\Structures::class)->name('admin.structures');
        Route::get('/punishments', \App\Livewire\Admin\Punishments::class)->name('admin.punishments');
        Route::get('/weapons', \App\Livewire\Admin\Weapons::class)->name('admin.weapons');
        Route::get('/work-norms', \App\Livewire\Admin\WorkNorms::class)->name('admin.work-norms');
        Route::get('/languages', \App\Livewire\Admin\Languages::class)->name('admin.languages');
        Route::get('/scientific-degrees', \App\Livewire\Admin\ScientificDegrees::class)->name('admin.scientific-degrees');
        Route::get('/social-origins', \App\Livewire\Admin\SocialOrigins::class)->name('admin.social-origins');
        Route::get('/rank-reasons', \App\Livewire\Admin\RankReasons::class)->name('admin.rank-reasons');
        Route::get('/rank-categories', \App\Livewire\Admin\RankCategories::class)->name('admin.rank-categories');
    });


    // Livewire::setUpdateRoute(function ($handle) {
    //     return Route::post('/livewire/update', $handle);
    // });
});

require __DIR__.'/auth.php';
