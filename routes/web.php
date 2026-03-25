<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Web\SubscriberController;
use App\Http\Controllers\Web\Backend\BusinessProfileController;
use App\Http\Controllers\Api\Company\SubscriptionWebhookController;






Route::get('/',function (){
    return view('welcome');
})->middleware('authCheck')->name('home');

Route::get('/login',function (){
    return view('auth.login');
})->middleware('authCheck')->name('home');


// handle web routes

Route::post('/webhook/payment', [SubscriptionWebhookController::class, 'handleWebhook']);



Route::get('/run-migrate', function () {
    try {
        $output = Artisan::call('migrate:fresh');
        return response()->json([
            'message' => 'Migrations executed.',
            'output' => nl2br($output)
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while running migrations.',
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/run-migrate-fresh', function () {
    try {
        $output = Artisan::call('migrate:fresh', ['--seed' => true]);
        return response()->json([
            'message' => 'Migrations executed.',
            'output' => nl2br($output)
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while running migrations.',
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Run composer update
Route::get('/run-composer-update', function () {
    $output = shell_exec('composer update 2>&1');
    return response()->json([
        'message' => 'Composer update command executed.',
        'output' => nl2br($output)
    ]);
});
// Run optimize:clear
Route::get('/run-optimize-clear', function () {
    $output = Artisan::call('optimize:clear');
    return response()->json([
        'message' => 'Optimize clear command executed.',
        'output' => nl2br($output)
    ]);
});
// Run db:seed
Route::get('/run-db-seed', function () {
    $output = Artisan::call('db:seed', ['--force' => true]);
    return response()->json([
        'message' => 'Database seeding executed.',
        'output' => nl2br($output)
    ]);
});
// Run cache:clear
Route::get('/run-cache-clear', function () {
    $output = Artisan::call('cache:clear');
    return response()->json([
        'message' => 'Cache cleared.',
        'output' => nl2br($output)
    ]);
});
// Run queue:restart
Route::get('/run-queue-restart', function () {
    $output = Artisan::call('queue:restart');
    return response()->json([
        'message' => 'Queue workers restarted.',
        'output' => nl2br($output)
    ]);
});

// Create storage symbolic link
Route::get('/run-storage-link', function () {
    try {
        $output = Artisan::call('storage:link');
        return response()->json([
            'message' => 'Storage symbolic link created.',
            'output' => nl2br($output)
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while creating storage symbolic link.',
            'error' => $e->getMessage(),
        ], 500);
    }
});


Route::get('privacy-policy', [SubscriberController::class, 'index'])->name('privacy_policy');
Route::get('terms-conditions', [SubscriberController::class, 'termsConditions'])->name('terms_conditions');
Route::get('sign-in', [SubscriberController::class, 'userLogin'])->name('sign-in');
Route::get('user-profile', [SubscriberController::class, 'userProfile'])->name('user-profile');

Route::post('login-submit', [SubscriberController::class, 'submitLogin'])->name('login.submit');
Route::post('logout', [SubscriberController::class, 'Userlogout'])->name('logout');
Route::get('delete-account', [SubscriberController::class, 'deleteAccount'])->name('delete.account');


require __DIR__.'/auth.php';



