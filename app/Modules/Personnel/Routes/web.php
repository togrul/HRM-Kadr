<?php

use App\Modules\Personnel\Http\Controllers\PersonnelFileDownloadController;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\MyHr\MyHrDashboard;
use App\Modules\Personnel\Livewire\MyHr\SelfServiceRequestReviews;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', AllPersonnel::class)->name('home');
    Route::get('/my-hr', MyHrDashboard::class)->name('my-hr');
    Route::get('/self-service-reviews', SelfServiceRequestReviews::class)->name('self-service-reviews');
    Route::get('/personnel/files/{document}/download', PersonnelFileDownloadController::class)
        ->name('personnel.files.download');
});
