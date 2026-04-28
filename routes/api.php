<?php

use Stripe\ApiOperations\Search;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\FirebaseTokenController;
use App\Http\Controllers\Web\Backend\SplashController;
use App\Http\Controllers\Api\Employee\ReportController;
use App\Http\Controllers\Api\Search\SearchingController;
use App\Http\Controllers\Api\Company\JobManageController;
use App\Http\Controllers\Api\Employee\EmployeeController;
use App\Http\Controllers\Api\Employee\JobBoardController;
use App\Http\Controllers\Api\Employee\JobReportController;
use App\Http\Controllers\Api\Website\UserManageController;
use App\Http\Controllers\Web\Backend\SpecializeController;
use App\Http\Controllers\Api\Employee\ChatSystemController;
use App\Http\Controllers\Api\Company\SubscriptionController;
use App\Http\Controllers\Api\FirebaseNotificationController;
use App\Http\Controllers\Api\Company\ManageCompanyController;
use App\Http\Controllers\Api\User\Auth\SocialLoginController;
use App\Http\Controllers\Api\User\Auth\UserProfileController;
use App\Http\Controllers\Api\Employee\JobApplicationController;
use App\Http\Controllers\Api\User\Auth\ResetPasswordController;
use App\Http\Controllers\Api\User\Auth\AuthenticationController;
use App\Http\Controllers\Api\AppsComplain\AppsComplainController;
use App\Http\Controllers\Api\PrivacyPolicy\PrivacyPolicyController;
use App\Http\Controllers\Web\Backend\Settings\DynamicPageController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('splash', [SplashController::class, 'Splash']);

Route::get('privacy-policy', [DynamicPageController::class, 'privacyPolicy']);
Route::get('term-conditions', [DynamicPageController::class, 'agreement']);

Route::get('/employee/specialize', [SpecializeController::class, 'employee_list']);
Route::get('/company/specialize', [SpecializeController::class, 'company_list']);
Route::get('/job/categories', [SpecializeController::class, 'job_categories']);

/*
|--------------------------------------------------------------------------
| Guest Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

Broadcast::routes([
    'middleware' => ['auth:api'], // or 'auth:jwt' depending on guard
]);

Route::prefix('v1')->group(function () {
    Route::post('social/signin', [SocialLoginController::class, 'socialSignin']);
    Route::group(['middleware' => 'guest:api'], function () {
        // Authentication
        Route::post('/register', [AuthenticationController::class, 'register']); // DONE
        Route::post('/register-otp-verify', [AuthenticationController::class, 'RegistrationVerifyOtp']);
        Route::post('/resend-otp', [AuthenticationController::class, 'ResendOtp']);

        // Password Reset
        Route::post('forgot-password', [ResetPasswordController::class, 'forgotPassword']);
        Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
        Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
    });
});

Route::prefix('v1')->group(function () {
    Route::post('/update/role', [AuthenticationController::class, 'updateRole']);
    Route::post('/phone/verify-otp', [AuthenticationController::class, 'phoneVerifyOtp']);
    Route::post('/logout', [AuthenticationController::class, 'logout']);
    Route::delete('/delete-profile', [AuthenticationController::class, 'deleteProfile']);
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::get('/user-details', [UserProfileController::class, 'me']);

    //Apps report
    Route::post('/apps-report', [AppsComplainController::class, 'storeComplain']);
});

// search company
Route::prefix('v1')->group(function () {
    Route::get('/company/search', [EmployeeController::class, 'searchCompany']);
});

//  repoert
Route::post('/report', [ReportController::class, 'report']);
// featured job
Route::get('v1/employee/job/featured', [JobBoardController::class, 'featuredJobList']);

Route::middleware(['auth:jobseeker', 'role:jobseeker'])->prefix('v1')->group(function () {

    // Employee CRUD
    Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employee.index');
    Route::post('/employees/store', [EmployeeController::class, 'store'])->name('admin.employee.store');

    Route::post('employee/update/experience', [EmployeeController::class, 'addExperience']);
    Route::post('employee/update/certification', [EmployeeController::class, 'addCertification']);
    Route::post('employee/update/qualification', [EmployeeController::class, 'addQualification']);

    // User Profile and avatar
    Route::get('/employee/profile', [UserProfileController::class, 'employee_profile']);
    Route::post('/job-seeker/profile/update', [UserProfileController::class, 'employee_profile_update']);

    Route::post('/update-avatar', [UserProfileController::class, 'updateAvatar']);
    Route::post('/update-password', [UserProfileController::class, 'updatePassword']);
    Route::post('/update-card-info', [UserProfileController::class, 'updateCardInfo']);


    // job details
    Route::get('/employee/job/details/{id}', [JobBoardController::class, 'jobDetails']);

    // recommended job
    Route::get('/employee/job/recommended', [JobBoardController::class, 'recommendedJobList']);

    // job application
    Route::post('/employee/job/apply', [JobApplicationController::class, 'applyJob']);
    Route::get('/employee/job/details/{id}', [JobBoardController::class, 'jobSeekerjobDetails']);
    Route::get('/employee/applied/job/list', [JobApplicationController::class, 'getAppliedJobs']);
    Route::get('/employee/applied/job/preview', [JobApplicationController::class, 'appliedJobPreview']);
    Route::delete('/employee/applied/job/delete', [JobApplicationController::class, 'appliedJobDelete']);

    Route::post('/employee/jobsave', [JobApplicationController::class, 'saveJob']);
    Route::get('/employee/savejob/list', [JobApplicationController::class, 'savedJobList']);
    Route::delete('/employee/savejob/delete/{job_id}', [JobApplicationController::class, 'DeleteSaveJob']);


    Route::post('/employee/job/report', [JobReportController::class, 'jobReport']);
});

// company routes
Route::middleware(['auth:company', 'role:company'])->prefix('v1')->group(function () {

    // create company
    Route::get('/company/profile/show', [ManageCompanyController::class, 'getCompany']);
    Route::post('/company/create', [ManageCompanyController::class, 'store']);

    // add new project
    Route::post('/company/project/update', [ManageCompanyController::class, 'storeProject']);
    Route::post('/update-password', [UserProfileController::class, 'updatePassword']);
    // add new specialize
    Route::post('/company/specialize/update', [ManageCompanyController::class, 'updateSpecialize']);

    Route::get('/company/profile/edit', [UserProfileController::class, 'me']);
    // Route::post('/company/profile/update', [UserProfileController::class, 'updateProfile']);

    // job management
    Route::post('/company/job/store', [JobManageController::class, 'store']);
    Route::get('/company/job/list', [JobManageController::class, 'getAllJobs']);
    Route::get('/company/alljob/list', [JobManageController::class, 'getCompanyAllJobs']);
    Route::get('/company/job/show/{id}', [JobManageController::class, 'jobDetails']);
    Route::post('/company/job/update/{id}', [JobManageController::class, 'update']);
    Route::delete('/company/job/delete/{id}', [JobManageController::class, 'jobDelete']);
    Route::get('/company/jobapplicant/list', [JobManageController::class, 'companyJobApplicants']);
    Route::get('/company/jobapplicant/list/{id}', [JobManageController::class, 'jobApplicantList']);



    // job applicants
    Route::get('/company/job/applicants/{id}', [JobManageController::class, 'jobApplicants']);
    Route::get('company/job/applicant/employee/{employeeId}', [JobManageController::class, 'jobApplicantDetails']);

    // employee details
    Route::get('company/job/applicant/employee/details/{id}', [JobManageController::class, 'employee_details']);
});

// chat with company route
Route::middleware(['auth:api'])->controller(ChatSystemController::class)->prefix('auth/chat')->group(function () {

    Route::get('/rooms', 'get_rooms');
    Route::get('/user/room/{room_id}', 'get_room_meesage');

    // get conversation
    Route::get('/conversation/{receiver_id}', 'get_conversation');


    Route::post('/send/message', 'send_message');
});

Route::prefix('v1')->group(function () {
    Route::post('/user/role/swotch', [UserProfileController::class, 'roleSwitching']);
});

// website route list

// Route::middleware('auth')->prefix('auth')->group(function () {

Route::get('/website/user/details', [UserManageController::class, 'user_info']);
Route::post('/website/user/avatar/update', [UserManageController::class, 'user_avatar_update']);

// job list

Route::get('/website/featured/jobs/list', [UserManageController::class, 'featuredJobList']);
Route::get('/website/recommanded/jobs/list', [UserManageController::class, 'recommadedJobList']);

Route::get('website/company/job/list', [UserManageController::class, 'companyJobList']);

Route::get('website/company/job/applicants/{id}', [UserManageController::class, 'jobApplicantList']);

// });

// subscription routes

Route::middleware(['auth:company', 'role:company'])->prefix('auth')->group(function () {

    Route::get('/subscription/plan', [SubscriptionController::class, 'getPlans']);
    Route::get('/subscription/plan/{id}', [SubscriptionController::class, 'getPlanDetails']);
    Route::post('/subscription/setup-intent', [SubscriptionController::class, 'createSetupIntent']);
    Route::post('/subscription/create', [SubscriptionController::class, 'createSubscription']);
    Route::post('/subscription/update', [SubscriptionController::class, 'updateSubscription']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription']);
    Route::post('/subscription/resume', [SubscriptionController::class, 'resumeSubscription']);
    Route::get('/subscription/status', [SubscriptionController::class, 'subscriptionStatus']);
});

Route::prefix('v1')->group(function () {
    Route::post('/company/profile/update', [UserProfileController::class, 'updateProfile']);
});
Route::prefix('v1')->group(function () {
    Route::post('/jobs/search', [SearchingController::class, 'search']);
    Route::get('/my/search', [SearchingController::class, 'mySearches']);
    Route::delete('/delete-search/{id}', [SearchingController::class, 'delete']);
    Route::delete('/clear-all-searches', [SearchingController::class, 'clearAll']);
});

Route::prefix('v1')->group(function () {
    Route::get('/privacy-policy', [PrivacyPolicyController::class, 'privacy']);
    Route::get('/terms-and-conditions', [PrivacyPolicyController::class, 'terms']);
});
Route::prefix('v1')->group(function () {
    Route::post('/firebase/save-token', [FirebaseNotificationController::class, 'saveToken']);
});


Route::prefix('v1')->controller(FirebaseTokenController::class)->group(function () {
    Route::get('test', 'test');
    Route::post('firebase/save-token', 'store');
    Route::post('token/get', 'getToken');
    Route::post('token/delete', 'deleteToken');
});
