<?php

use App\Http\Controllers\Api\AppSettingsController;
use App\Http\Controllers\Api\Merchant\Auth\LoginController;
use App\Http\Controllers\Api\Merchant\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Merchant\AuthorizationController;
use App\Http\Controllers\Api\Merchant\MoneyOutController;
use App\Http\Controllers\Api\Merchant\UserController;
use App\Http\Controllers\Api\Merchant\ReceiveMoneyController;
use App\Http\Controllers\Api\Merchant\SecurityController;
use App\Http\Controllers\Api\Merchant\TransactionController;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\SetupKyc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    $message =  ['success'=>['Clear cache successfully']];
    return Helpers::onlysuccess($message);
});
Route::controller(AppSettingsController::class)->prefix("app-settings")->group(function(){
    Route::get('/','appSettings');
    Route::get('languages','languages');
});
Route::prefix('merchant')->group(function(){
    Route::get('get/basic/data', function() {
        $user_kyc = SetupKyc::merchantKyc()->first();
        $data =[
            'mobile_code' => getDialCode(),
            'register_kyc_fields' =>$user_kyc,
            'countries' =>get_all_countries()
        ];
        $message =  ['success'=>['Basic information fetch successfully']];
        return Helpers::success($data,$message);
    });
     //email verify before register
     Route::prefix('register')->group(function(){
        Route::post('check/exist',[AuthorizationController::class,'checkExist']);
        Route::post('send/otp', [AuthorizationController::class,'sendEmailOtp']);
        Route::post('verify/otp',[AuthorizationController::class,"verifyEmailOtp"]);
        Route::post('resend/otp',[AuthorizationController::class,"resendEmailOtp"]);
    });
    Route::post('login',[LoginController::class,'login']);
    Route::post('register',[LoginController::class,'register']);
  //forget password for email
    Route::prefix('forget')->group(function(){
        Route::post('password', [ForgotPasswordController::class,'sendCode']);
        Route::post('verify/otp', [ForgotPasswordController::class,'verifyCode']);
        Route::post('reset/password', [ForgotPasswordController::class,'resetPassword']);
    });

    Route::middleware(['merchant.api'])->group(function(){
        Route::get('logout', [LoginController::class,'logout']);
        Route::post('google/2fa/verify', [SecurityController::class,'verifyGoogle2Fa']);
        //account re-verifications
        Route::post('send-code', [AuthorizationController::class,'sendMailCode']);
        Route::post('email-verify', [AuthorizationController::class,'mailVerify']);
        Route::middleware(['CheckStatusApiMerchant','merchant.google.two.factor.api'])->group(function () {
            Route::get('dashboard', [UserController::class,'home']);
            Route::get('profile', [UserController::class,'profile']);
            Route::post('profile/update', [UserController::class,'profileUpdate']);
            Route::post('password/update', [UserController::class,'passwordUpdate']);
            Route::post('delete/account', [UserController::class,'deleteAccount']);
            Route::get('notifications', [UserController::class,'notifications']);
            Route::get('kyc', [AuthorizationController::class,'showKycFrom']);
            Route::post('kyc/submit', [AuthorizationController::class,'kycSubmit']);
            //Receive Money
            Route::controller(ReceiveMoneyController::class)->prefix('receive-money')->group(function(){
                Route::get('/','index');
            });

             //Money Out
            Route::controller(MoneyOutController::class)->prefix('withdraw')->group(function(){
                Route::get('info','moneyOutInfo');
                Route::post('insert','moneyOutInsert');
                Route::post('manual/confirmed','moneyOutConfirmed')->name('merchant.api.withdraw.manual.confirmed');
                Route::post('automatic/confirmed','confirmMoneyOutAutomatic')->name('merchant.api.withdraw.automatic.confirmed');
                 //check flutterwave banks
                 Route::post('check/flutterwave/bank','checkBanks');
            });
             //transactions
            Route::controller(TransactionController::class)->prefix("transactions")->group(function(){
                Route::get('/{slug?}','index')->whereIn('slug',['add-money','money-out','transfer-money','money-exchange','bill-pay','mobile-topup','virtual-card','remittance']);
                Route::post('search','search');
            });
             //google-2fa
            Route::controller(SecurityController::class)->prefix("security")->group(function(){
                Route::get('google/2fa/status','google2FA');

            });

        });

    });

});
