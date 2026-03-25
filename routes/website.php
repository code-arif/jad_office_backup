<?php

use App\Http\Controllers\Api\Website\UserManageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes (No Auth Required)
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => 'guest:api'], function () {

});


// company routes
Route::middleware(['auth:company', 'role:company'])->prefix('auth')->group(function () {


});
