<?php

use App\Modules\Personnel\Http\Controllers\OnboardingTemplateFileController;
use App\Modules\Personnel\Http\Controllers\PersonnelFileDownloadController;
use App\Modules\Personnel\Http\Controllers\PersonnelPaletteSearchController;
use App\Modules\Personnel\Http\Controllers\PersonnelPhotoController;
use App\Modules\Personnel\Http\Controllers\PortfolioAttachmentController;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\Home;
use App\Modules\Personnel\Livewire\MyHr\MyHrDashboard;
use App\Modules\Personnel\Livewire\MyHr\SelfServiceRequestReviews;
use App\Modules\Personnel\Livewire\PersonnelProfile;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/personnel', AllPersonnel::class)->name('personnel.index');
    Route::get('/personnel/palette-search', PersonnelPaletteSearchController::class)
        ->middleware('throttle:120,1')
        ->name('personnel.palette-search');
    Route::get('/personnel/{personnel}', PersonnelProfile::class)->name('personnel.show');
    Route::get('/my-hr', MyHrDashboard::class)->name('my-hr');
    Route::get('/self-service-reviews', SelfServiceRequestReviews::class)->name('self-service-reviews');
    Route::get('/personnel/files/{document}/download', PersonnelFileDownloadController::class)
        ->name('personnel.files.download');
    Route::get('/personnel/photos/{personnel}', PersonnelPhotoController::class)
        ->whereNumber('personnel')
        ->name('personnel.photo');
    Route::get('/personnel/portfolio-attachments/{attachment}', PortfolioAttachmentController::class)
        ->whereNumber('attachment')
        ->name('personnel.portfolio-attachments.show');
    Route::get('/onboarding-documents/{template}/file', OnboardingTemplateFileController::class)
        ->whereNumber('template')
        ->name('onboarding.templates.file');
});
