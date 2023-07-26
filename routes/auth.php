<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Agent\Auth\ForgotPasswordController as AgentAuthForgotPasswordController;
use App\Http\Controllers\Agent\Auth\LoginController as AgentAuthLoginController;
use App\Http\Controllers\Agent\Auth\RegisterController as AuthRegisterController;
use App\Http\Controllers\Agent\AuthorizationController as AgentAuthorizationController;
use App\Http\Controllers\Merchant\Auth\ForgotPasswordController as AuthForgotPasswordController;
use App\Http\Controllers\Merchant\Auth\LoginController as AuthLoginController;
use App\Http\Controllers\Merchant\Auth\RegisterController;
use App\Http\Controllers\Merchant\AuthorizationController as MerchantAuthorizationController;
use App\Http\Controllers\User\Auth\ForgotPasswordController as UserForgotPasswordController;
use App\Http\Controllers\User\Auth\LoginController as UserLoginController;
use App\Http\Controllers\User\Auth\RegisterController as UserRegisterController;
use App\Http\Controllers\User\Auth\SocialAuthentication;
use App\Http\Controllers\User\AuthorizationController;

// Admin Authentication Route
Route::middleware(['guest','admin.login.guard'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('/',function(){
        return redirect()->route('admin.login');
    });
    Route::get('login',[LoginController::class,"showLoginForm"])->name('login');
    Route::post('login/submit',[LoginController::class,"login"])->name('login.submit');

    Route::get('password/forgot',[ForgotPasswordController::class,"showLinkRequestForm"])->name('password.forgot');
    Route::post('password/forgot',[ForgotPasswordController::class,"sendResetLinkEmail"])->name('password.forgot.request');

    Route::get('password/reset/{token}',[ResetPasswordController::class,"showResetForm"])->name('password.reset');
    Route::post('password/update',[ResetPasswordController::class,'reset'])->name('password.update');
});

Route::name('user.')->group(function(){
    Route::get('login',[UserLoginController::class,"showLoginForm"])->name('login');
    Route::post('login',[UserLoginController::class,"login"])->name('login.submit');

    Route::get('register',[UserRegisterController::class,"showRegistrationForm"])->name('register');
    Route::post('register',[UserRegisterController::class,"register"])->name('register.submit');
    Route::post('send/verify-code',[UserRegisterController::class,"sendVerifyCode"])->name('send.code');
    Route::get('email/verify/{token}',[AuthorizationController::class,"showMailFormBeforRegister"])->name('email.verify');
    Route::post('verify/code/{token}',[UserRegisterController::class,"verifyCode"])->name('verify.code');
    Route::get('resend/code',[UserRegisterController::class,"resendCode"])->name('resend.code');
    Route::get('register/kyc',[UserRegisterController::class,"registerKyc"])->name('register.kyc');

    // recovery password by email
    Route::controller(UserForgotPasswordController::class)->prefix("password")->name("password.")->group(function(){
        Route::get('forgot','showForgotForm')->name('forgot');
        Route::post('forgot/send/code','sendCode')->name('forgot.send.code');
        Route::get('forgot/code/verify/form/{token}','showVerifyForm')->name('forgot.code.verify.form');
        Route::post('forgot/verify/{token}','verifyCode')->name('forgot.verify.code');
        Route::get('forgot/resend/code/{token}','resendCode')->name('forgot.resend.code');
        Route::get('forgot/reset/form/{token}','showResetForm')->name('forgot.reset.form');
        Route::post('forgot/reset/{token}','resetPassword')->name('reset');

    });
    Route::controller(AuthorizationController::class)->prefix("authorize")->name('authorize.')->middleware("auth")->group(function(){
        Route::get('mail/{token}','showMailFrom')->name('mail');
        Route::post('mail/verify/{token}','mailVerify')->name('mail.verify');
        Route::get('resend/code','resendCode')->name('resend.code');
        Route::get('kyc','showKycFrom')->name('kyc');
        Route::post('kyc/submit','kycSubmit')->name('kyc.submit');
        Route::get('google/2fa','showGoogle2FAForm')->name('google.2fa');
        Route::post('google/2fa/submit','google2FASubmit')->name('google.2fa.submit');


    });
});
// //merchants
Route::prefix('merchant')->name('merchant.')->group(function(){
    Route::get('/',function(){
        return redirect()->route('merchant.login');
    });
    Route::get('login',[AuthLoginController::class,"showLoginForm"])->name('login');
    Route::post('login',[AuthLoginController::class,"login"])->name('login.submit');

    //register
    Route::get('register',[RegisterController::class,"showRegistrationForm"])->name('register');
    Route::post('register',[RegisterController::class,"register"])->name('register.submit');
    Route::post('send/verify-code',[RegisterController::class,"sendVerifyCode"])->name('send.code');
    Route::get('email/verify/{token}',[MerchantAuthorizationController::class,"showSmsFromRegister"])->name('email.verify');
    Route::post('verify/code/{token}',[RegisterController::class,"verifyCode"])->name('verify.code');
    Route::get('resend/code',[RegisterController::class,"resendCode"])->name('resend.code');
    Route::get('register/kyc',[RegisterController::class,"registerKyc"])->name('register.kyc');

     // recovery password by email
     Route::controller(AuthForgotPasswordController::class)->prefix("password")->name("password.")->group(function(){
        Route::get('forgot','showForgotForm')->name('forgot');
        Route::post('forgot/send/code','sendCode')->name('forgot.send.code');
        Route::get('forgot/code/verify/form/{token}','showVerifyForm')->name('forgot.code.verify.form');
        Route::post('forgot/verify/{token}','verifyCode')->name('forgot.verify.code');
        Route::get('forgot/resend/code/{token}','resendCode')->name('forgot.resend.code');
        Route::get('forgot/reset/form/{token}','showResetForm')->name('forgot.reset.form');
        Route::post('forgot/reset/{token}','resetPassword')->name('reset');

    });

    Route::controller(MerchantAuthorizationController::class)->prefix("authorize")->name('authorize.')->middleware("auth:merchant")->group(function(){
        Route::get('mail/{token}','showMailFrom')->name('mail');
        Route::post('mail/verify/{token}','mailVerify')->name('mail.verify');
        Route::get('resend/code','resendCode')->name('resend.code');
        Route::get('kyc','showKycFrom')->name('kyc');
        Route::post('kyc/submit','kycSubmit')->name('kyc.submit');
        Route::get('google/2fa','showGoogle2FAForm')->name('google.2fa');
        Route::post('google/2fa/submit','google2FASubmit')->name('google.2fa.submit');

    });
});
