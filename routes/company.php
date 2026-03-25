<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Business\Auth\BusinessAuthController;
use App\Http\Controllers\Api\Business\Auth\BusinessProfileController;
use App\Http\Controllers\Api\Company\ManageCompanyController;



Route::middleware(['auth:employee', 'role:employee'])->prefix('auth')->group(function () {
    Route::get('/business-profile', [BusinessProfileController::class, 'getProfile']);
    Route::post('/business-profile/update', [BusinessAuthController::class, 'updateProfile']);
    Route::post('/business-menu/update', [BusinessAuthController::class, 'editMenu']);
    Route::post('/business-password/update', [BusinessProfileController::class, 'updatePassword']);
    Route::delete('/business-profile/delete', [BusinessProfileController::class, 'deleteBusinessProfile']);
    Route::post('/logout', [BusinessProfileController::class, 'logout']);
});
