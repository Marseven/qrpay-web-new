<?php

use App\Http\Controllers\Api\AppSettingsController;
use App\Http\Controllers\Api\User\AddMoneyController;
use App\Http\Controllers\Api\Agent\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Agent\Auth\LoginController;
use App\Http\Controllers\Api\User\AuthorizationController;
use App\Http\Controllers\Api\User\BillPayController;
use App\Http\Controllers\Api\User\MakePaymentController;
use App\Http\Controllers\Api\User\MobileTopupController;
use App\Http\Controllers\Api\User\MoneyOutController;
use App\Http\Controllers\Api\User\ReceiveMoneyController;
use App\Http\Controllers\Api\User\RecipientController;
use App\Http\Controllers\Api\User\RemittanceController;
use App\Http\Controllers\Api\User\SecurityController;
use App\Http\Controllers\Api\User\SendMoneyController;
use App\Http\Controllers\Api\User\TransactionController;
use App\Http\Controllers\Api\Agent\UserController;
use App\Http\Controllers\Api\User\VirtualCardController;
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
Route::get('get/basic/data', function() {
    $user_kyc = SetupKyc::agentKyc()->first();
    $data =[
        'mobile_code' => getDialCode(),
        'register_kyc_fields' =>$user_kyc,
        'countries' =>all_countries()
    ];
    $message =  ['success'=>['Basic information fetch successfully']];
    return Helpers::success($data,$message);
});
Route::controller(AppSettingsController::class)->prefix("app-settings")->group(function(){
    Route::get('/','appSettings');
    Route::get('languages','languages');
});
Route::controller(AddMoneyController::class)->prefix("add-money")->group(function(){
    Route::get('success/response/{gateway}','success')->name('api.payment.success');
    Route::get("cancel/response/{gateway}",'cancel')->name('api.payment.cancel');
});

Route::prefix('user')->group(function(){
    Route::post('login',[LoginController::class,'login']);
    Route::post('check/exist',[LoginController::class,'checkExist']);
    Route::post('register',[LoginController::class,'register']);

    Route::post('forget/password/check/user', [ForgotPasswordController::class,'sendCodeSms']);
    Route::post('forget/reset/password', [ForgotPasswordController::class,'resetPasswordSms']);

    Route::middleware(['agent.api'])->group(function(){
        Route::get('logout', [LoginController::class,'logout']);
        //email verifications
        // Route::post('send-code', [AuthorizationController::class,'sendMailCode']);
        // Route::post('email-verify', [AuthorizationController::class,'mailVerify']);
        Route::post('sms/verify', [AuthorizationController::class,'smsVerify']);
        Route::get('kyc', [AuthorizationController::class,'showKycFrom']);
        Route::post('kyc/submit', [AuthorizationController::class,'kycSubmit']);
        Route::post('google/2fa/verify', [SecurityController::class,'verifyGoogle2Fa']);
        Route::middleware(['CheckStatusApiAgent','user.google.two.factor.api'])->group(function () {
            Route::get('dashboard', [UserController::class,'home']);
            Route::get('profile', [UserController::class,'profile']);
            Route::post('profile/update', [UserController::class,'profileUpdate']);
            Route::post('password/update', [UserController::class,'passwordUpdate']);
            Route::post('delete/account', [UserController::class,'deleteAccount']);
            Route::get('notifications', [UserController::class,'notifications']);
             //add money
            Route::controller(AddMoneyController::class)->prefix("add-money")->group(function(){
                Route::get('/information','addMoneyInformation');
                Route::post('submit-data','submitData');
                //automatic
                Route::post('stripe/payment/confirm','paymentConfirmedApi')->name('api.stripe.payment.confirmed');
                //manual gateway
                Route::post('manual/payment/confirmed','manualPaymentConfirmedApi')->name('api.manual.payment.confirmed');

            });
            //Receive Money
            Route::controller(ReceiveMoneyController::class)->prefix('receive-money')->group(function(){
                Route::get('/','index');
            });
             //Send Money
            Route::controller(SendMoneyController::class)->prefix('send-money')->group(function(){
                Route::get('info','sendMoneyInfo');
                Route::post('exist','checkUser');
                Route::post('qr/scan','qrScan');
                Route::post('confirmed','confirmedSendMoney');
            });
             //Money Out
            Route::controller(MoneyOutController::class)->prefix('money-out')->group(function(){
                Route::get('info','moneyOutInfo');
                Route::post('insert','moneyOutInsert');
                Route::post('confirmed','moneyOutConfirmed');
            });
             //Make Payment
             Route::controller(MakePaymentController::class)->prefix('make-payment')->group(function(){
                Route::get('info','makePaymentInfo');
                Route::post('check/merchant','checkMerchant');
                Route::post('merchants/scan','qrScan');
                Route::post('confirmed','confirmedPayment');
            });
             //Bill Pay
            Route::controller(BillPayController::class)->prefix('bill-pay')->group(function(){
                Route::get('info','billPayInfo');
                Route::post('confirmed','billPayConfirmed');
            });
             //mobile top up
            Route::controller(MobileTopupController::class)->prefix('mobile-topup')->group(function(){
                Route::get('info','topUpInfo');
                Route::post('confirmed','topUpConfirmed');
            });
             //Saved Recipient
            Route::controller(RecipientController::class)->prefix('recipient')->group(function(){
                Route::get('list','recipientList');
                Route::get('save/info','saveRecipientInfo');
                Route::get('dynamic/fields','dynamicFields');
                Route::post('check/user','checkUser');
                Route::post('store','storeRecipient');
                Route::get('edit','editRecipient');
                Route::post('update','updateRecipient');
                Route::post('delete','deleteRecipient');
            });
             //Remitance
            Route::controller(RemittanceController::class)->prefix('remittance')->group(function(){
                Route::get('info','remittanceInfo');
                Route::post('confirmed','confirmed');
                //for filters
                Route::post('get/recipient','getRecipient');
                // Route::post('get/recipient/transaction/type','getRecipientByTransType');
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
