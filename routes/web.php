<?php

use App\Http\Controllers\MenuController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\User\AddMoneyController;
use Illuminate\Support\Facades\Route;

use Flutterwave\Service\Transfer;
use KingFlamez\Rave\Facades\Rave;
use Flutterwave\Flutterwave\Misc;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//landing pages
Route::controller(SiteController::class)->group(function () {
    Route::get('/', 'home')->name('index');
    Route::get('about', 'about')->name('about');
    Route::get('service', 'service')->name('service');
    Route::get('faq', 'faq')->name('faq');
    Route::get('web/journal', 'blog')->name('blog');
    Route::get('web/journal/details/{id}/{slug}', 'blogDetails')->name('blog.details');
    Route::get('web/journal/by/category/{id}/{slug}', 'blogByCategory')->name('blog.by.category');
    Route::get('merchant-info', 'merchant')->name('merchant');
    Route::get('contact', 'contact')->name('contact');
    Route::post('contact/store', 'contactStore')->name('contact.store');
    Route::get('change/{lang?}', 'changeLanguage')->name('lang');
    Route::get('page/{slug}', 'usefulPage')->name('useful.link');
    Route::post('newsletter', 'newsletterSubmit')->name('newsletter.submit');
});

Route::post('/ebilling/notify', [AddMoneyController::class, 'ebillingNotify'])->name('ebilling.notify');
Route::prefix("/menu")->controller(MenuController::class)->group(function (){
    Route::get("/","getMenu");
});