<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Backend\FaqController;
use App\Http\Controllers\Web\Backend\CategoryController;
use App\Http\Controllers\Web\Backend\UserListController;
use App\Http\Controllers\Web\Backend\DashboardController;
use App\Http\Controllers\Web\Backend\TestimonialController;
use App\Http\Controllers\Web\Backend\CMS\AuthPageController;
use App\Http\Controllers\Web\Backend\BusinessProfileController;
use App\Http\Controllers\Web\Backend\ChatManageController;
use App\Http\Controllers\Web\Backend\EmployeeCompanyController;
use App\Http\Controllers\web\Backend\PlanController;
use App\Http\Controllers\web\backend\PlanfeatureController;
use App\Http\Controllers\Web\Backend\Settings\SocialController;
use App\Http\Controllers\Web\Backend\Settings\StripeController;
use App\Http\Controllers\Web\Backend\Settings\ProfileController;
use App\Http\Controllers\Web\Backend\Settings\SettingController;
use App\Http\Controllers\Web\Backend\Settings\FirebaseController;
use App\Http\Controllers\Web\Backend\Settings\DynamicPageController;
use App\Http\Controllers\Web\Backend\Settings\MailSettingController;
use App\Http\Controllers\Web\Backend\Settings\SocialSettingController;
use App\Http\Controllers\Web\Backend\SpecializeController;


Route::middleware(['auth:web', 'admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/business-profile/pending', [BusinessProfileController::class, 'pendingProfiles'])->name('admin.business_profile.pending');
    Route::post('/business-profile/approve/{id}', [BusinessProfileController::class, 'approveProfile'])->name('admin.business_profile.approve');
    Route::post('/business-profile/cancel/{id}', [BusinessProfileController::class, 'cancelProfile'])->name('admin.business_profile.cancel');
    Route::get('/business-profile/{id}', [BusinessProfileController::class, 'profileDetails'])->name('admin.business_profile.show');
});





Route::middleware(['auth:web'])->group(function () {

    Route::get('category', [CategoryController::class, 'index'])->name('admin.category.index');
    Route::get('category/create', [CategoryController::class, 'create'])->name('admin.category.create');
    Route::post('category/store', [CategoryController::class, 'store'])->name('admin.category.store');
    Route::get('category/edit/{id}', [CategoryController::class, 'edit'])->name('admin.category.edit');
    Route::put('category/update/{id}', [CategoryController::class, 'update'])->name('admin.category.update');
    Route::delete('category/delete/{id}', [CategoryController::class, 'destroy'])->name('admin.category.destroy');
    Route::post('/category/status/{id}', [CategoryController::class, 'status'])->name('admin.category.status');
});


Route::middleware(['auth:web'])->group(function () {

    Route::get('specialize', [SpecializeController::class, 'index'])->name('admin.specialize.index');
    Route::get('specialize/create', [SpecializeController::class, 'create'])->name('admin.specialize.create');
    Route::post('specialize/store', [SpecializeController::class, 'store'])->name('admin.specialize.store');
    Route::get('specialize/edit/{id}', [SpecializeController::class, 'edit'])->name('admin.specialize.edit');
    Route::put('specialize/update/{id}', [SpecializeController::class, 'update'])->name('admin.specialize.update');
    Route::delete('specialize/delete/{id}', [SpecializeController::class, 'destroy'])->name('admin.specialize.destroy');
    Route::post('/specialize/status/{id}', [SpecializeController::class, 'status'])->name('admin.specialize.status');
});



Route::controller(ChatManageController::class)->prefix('chat')->name('admin.chat.')->group(function () {

    Route::get('/', 'index')->name('index');
    Route::get('/list', 'list')->name('list');
    Route::post('/send/{receiver_id}', 'send')->name('send');
    Route::get('/conversation/{receiver_id}', 'conversation')->name('conversation');
    Route::get('/room/{receiver_id}', 'room');
    Route::get('/search', 'search')->name('search');
    Route::get('/seen/all/{receiver_id}', 'seenAll');
    Route::get('/seen/single/{chat_id}', 'seenSingle');
});









Route::get('/user-list', [UserListController::class, 'index'])->name('admin.user.index');
Route::delete('/user-list/delete/{id}', [UserListController::class, 'destroy'])->name('admin.user.destroy');


Route::controller(FaqController::class)->group(function () {
    Route::get('/faq', 'index')->name('admin.faq.index');
    Route::get('/faq/create', 'create')->name('admin.faq.create');
    Route::post('/faq', 'store')->name('admin.faq.store');
    Route::get('/faq/edit/{id}', 'edit')->name('admin.faq.edit');
    Route::put('/faq/{id}', 'update')->name('admin.faq.update');
    Route::post('/faq/status/{id}', 'status')->name('admin.faq.status');
    Route::delete('/faq/{id}', 'destroy')->name('admin.faq.destroy');
});


Route::get('/testimonials', [TestimonialController::class, 'index'])->name('admin.testimonial.index');
Route::post('/testimonial/status/{id}', [TestimonialController::class, 'status'])->name('admin.testimonial.status');
Route::delete('/testimonial/delete/{id}', [TestimonialController::class, 'destroy'])->name('admin.testimonial.destroy');


Route::get('/admin/social-media-settings', [SocialSettingController::class, 'index'])->name('admin.social_media.index');
Route::get('/admin/social-media/{id}/edit', [SocialSettingController::class, 'edit'])->name('admin.social_media.edit');
Route::put('/admin/social-media/{id}', [SocialSettingController::class, 'update'])->name('admin.social_media.update');





//! Route for Profile Settings
Route::controller(ProfileController::class)->group(function () {
    Route::get('setting/profile', 'index')->name('setting.profile.index');
    Route::put('setting/profile/update', 'UpdateProfile')->name('setting.profile.update');
    Route::put('setting/profile/update/Password', 'UpdatePassword')->name('setting.profile.update.Password');
    Route::post('setting/profile/update/Picture', 'UpdateProfilePicture')->name('update.profile.picture');
});

//! Route for Mail Settings
Route::controller(MailSettingController::class)->group(function () {
    Route::get('setting/mail', 'index')->name('setting.mail.index');
    Route::patch('setting/mail', 'update')->name('setting.mail.update');
});

//! Route for Stripe Settings
Route::controller(StripeController::class)->prefix('setting/stripe')->name('setting.stripe.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

//! Route for Firebase Settings
Route::controller(FirebaseController::class)->prefix('setting/firebase')->name('setting.firebase.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

//! Route for Firebase Settings
Route::controller(SocialController::class)->prefix('setting/social')->name('setting.social.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/update', 'update')->name('update');
});

//! Route for Stripe Settings
Route::controller(SettingController::class)->group(function () {
    Route::get('setting/general', 'index')->name('setting.general.index');
    Route::patch('setting/general', 'update')->name('setting.general.update');
});


//CMS
Route::controller(AuthPageController::class)->prefix('cms')->name('cms.')->group(function () {
    Route::get('page/auth/section/bg', 'index')->name('page.auth.section.bg.index');
    Route::patch('page/auth/section/bg', 'update')->name('page.auth.section.bg.update');
});


Route::controller(DynamicPageController::class)->group(function () {
    Route::get('/dynamic-page', 'index')->name('admin.dynamic_page.index');
    Route::get('/dynamic-page/create', 'create')->name('admin.dynamic_page.create');
    Route::post('/dynamic-page/store', 'store')->name('admin.dynamic_page.store');
    Route::get('/dynamic-page/edit/{id}', 'edit')->name('admin.dynamic_page.edit');
    Route::put('/dynamic-page/update/{id}', 'update')->name('admin.dynamic_page.update');
    Route::post('/dynamic-page/status/{id}', 'status')->name('admin.dynamic_page.status');
    Route::delete('/dynamic-page/destroy/{id}', 'destroy')->name('admin.dynamic_page.destroy');
});


Route::controller(EmployeeCompanyController::class)->group(function () {
    Route::get('/employees', 'index')->name('admin.employees.index');
    Route::get('/company', 'index2')->name('admin.company.index');
    Route::get('/employees/{id}', 'show')->name('admin.employees.show');
    Route::get('/company/{id}', 'show2')->name('admin.company.show');
    Route::get('/company/Jobs/{id}', 'showJobs')->name('admin.company.jobs');
});


Route::resource('subscriptions-plans', PlanController::class);
Route::resource('planfeatures', PlanfeatureController::class);
