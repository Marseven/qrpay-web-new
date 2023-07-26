<?php

use App\Http\Controllers\Agent\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GlobalController;
use App\Providers\Admin\BasicSettingsProvider;
use App\Http\Controllers\User\WalletController;
use Pusher\PushNotifications\PushNotifications;
use App\Http\Controllers\Agent\BillPayController;
use App\Http\Controllers\Agent\ProfileController;
use App\Http\Controllers\Agent\ReceiveMoneyController;
use App\Http\Controllers\Agent\SendMoneyController;
use App\Http\Controllers\Agent\AddMoneyController;
use App\Http\Controllers\Agent\MoneyOutController;
use App\Http\Controllers\User\SecurityController;
use App\Http\Controllers\User\RemitanceController;
use App\Http\Controllers\User\ReceipientController;
use App\Http\Controllers\Agent\MobileTopupController;
use App\Http\Controllers\Agent\TransactionController;
use App\Http\Controllers\User\SupportTicketController;

Route::prefix("agent")->name("agent.")->middleware(['checkStatus'])->group(function(){
    Route::post("info",[GlobalController::class,'userInfo'])->name('info');
    Route::controller(DashboardController::class)->group(function(){
        Route::get('dashboard','index')->name('dashboard');
        Route::get('qr/scan/{qr_code}','qrScan')->name('qr.scan');
        Route::post('logout','logout')->name('logout');
    });

    //profile
    Route::controller(ProfileController::class)->prefix("profile")->name("profile.")->group(function(){
        Route::get('/','index')->name('index');
        Route::put('password/update','passwordUpdate')->name('password.update');
        Route::put('update','update')->name('update');
    });
     //Send Money
     Route::controller(SendMoneyController::class)->prefix('send-money')->name('send.money.')->group(function(){
        Route::get('/','index')->name('index');
        Route::post('confirmed','confirmed')->name('confirmed');
        Route::post('user/exist','checkUser')->name('check.exist');
    });
     //Receive Money
     Route::controller(ReceiveMoneyController::class)->prefix('receive-money')->name('receive.money.')->group(function(){
        Route::get('/','index')->name('index');
    });
    Route::controller(WalletController::class)->prefix("wallets")->name("wallets.")->group(function(){
        Route::get("/","index")->name("index");
        Route::post("balance","balance")->name("balance");
    });

    //add money
    Route::controller(AddMoneyController::class)->prefix("add-money")->name("add.money.")->group(function(){
        Route::get('/','index')->name("index");
        Route::post('submit','submit')->name('submit');
        Route::get('success/response/{gateway}','success')->name('payment.success');
        Route::get("cancel/response/{gateway}",'cancel')->name('payment.cancel');
        // Controll AJAX Resuest
        // Route::post("xml/currencies","getCurrenciesXml")->name("xml.currencies");
        Route::get('payment/{gateway}','payment')->name('payment');
        Route::post('stripe/payment/confirm','paymentConfirmed')->name('stripe.payment.confirmed');
        //manual gateway
        Route::get('manual/payment','manualPayment')->name('manual.payment');
        Route::post('manual/payment/confirmed','manualPaymentConfirmed')->name('manual.payment.confirmed');

    });
    //money out
    Route::controller(MoneyOutController::class)->prefix('money-out')->name('money.out.')->group(function(){
        Route::get('/','index')->name('index');
        Route::post('insert','paymentInsert')->name('insert');
        Route::get('preview','preview')->name('preview');
        Route::post('confirm','confirmMoneyOut')->name('confirm');
    });

    //bill pay
    Route::controller(BillPayController::class)->prefix('bill-pay')->name('bill.pay.')->group(function(){
        Route::get('/','index')->name('index');
        Route::post('insert','payConfirm')->name('confirm');
    });
    //Mobile Topup
    Route::controller(MobileTopupController::class)->prefix('mobile-topup')->name('mobile.topup.')->group(function(){
        Route::get('/','index')->name('index');
        Route::post('insert','payConfirm')->name('confirm');
    });
    //Receipient
    Route::controller(ReceipientController::class)->prefix('recipient')->name('receipient.')->group(function(){
        Route::get('/','index')->name('index');
        Route::get('/add','addReceipient')->name('add');
        Route::post('/add','storeReceipient');
        Route::get('edit/{id}','editReceipient')->name('edit');
        Route::put('update','updateReceipient')->name('update');
        Route::delete('delete','deleteReceipient')->name('delete');
        Route::post('find/user','checkUser')->name('check.user');
        Route::post('get/create-input','getTrxTypeInputs')->name('create.get.input');
        Route::post('get/edit-input','getTrxTypeInputsEdit')->name('edit.get.input');
        Route::get('send/remittance/{id}','sendRemittance')->name('send.remittance');
    });
    //Remitance
    Route::controller(RemitanceController::class)->prefix('remittance')->name('remittance.')->group(function(){
        Route::get('/','index')->name('index');
        Route::post('get/token','getToken')->name('get.token');
        Route::post('confirmed','confirmed')->name('confirmed');
        //for filters
        Route::post('get/recipient/country','getRecipientByCountry')->name('get.recipient.country');
        Route::post('get/recipient/transaction/type','getRecipientByTransType')->name('get.recipient.transtype');
    });


    //transactions
    Route::controller(TransactionController::class)->prefix("transactions")->name("transactions.")->group(function(){
        Route::get('/{slug?}','index')->name('index')->whereIn('slug',['add-money','money-out','transfer-money','bill-pay','mobile-topup','remittance','make-payment']);
        // Route::get('log/{slug?}','log')->name('log')->whereIn('slug',['add-money','money-out','transfer-money']);
        Route::post('search','search')->name('search');
    });
    //google-2fa
    Route::controller(SecurityController::class)->prefix("security")->name('security.')->group(function(){
        Route::get('google/2fa','google2FA')->name('google.2fa');
        Route::post('google/2fa/status/update','google2FAStatusUpdate')->name('google.2fa.status.update');
    });

    //support tickets
    Route::controller(SupportTicketController::class)->prefix("support/ticket")->name("support.ticket.")->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('conversation/{encrypt_id}','conversation')->name('conversation');
        Route::post('message/send','messageSend')->name('messaage.send');
    });

});
// Route::get('user/pusher/beams-auth', function (Request $request) {
//     if(Auth::check() == false) {
//         return response(['Inconsistent request'], 401);
//     }
//     $userID = Auth::user()->id;

//     $basic_settings = BasicSettingsProvider::get();
//     if(!$basic_settings) {
//         return response('Basic setting not found!', 404);
//     }

//     $notification_config = $basic_settings->push_notification_config;

//     if(!$notification_config) {
//         return response('Notification configuration not found!', 404);
//     }

//     $instance_id    = $notification_config->instance_id ?? null;
//     $primary_key    = $notification_config->primary_key ?? null;
//     if($instance_id == null || $primary_key == null) {
//         return response('Sorry! You have to configure first to send push notification.', 404);
//     }
//     $beamsClient = new PushNotifications(
//         array(
//             "instanceId" => $notification_config->instance_id,
//             "secretKey" => $notification_config->primary_key,
//         )
//     );
//     $publisherUserId = "user-".$userID;
//     try{
//         $beamsToken = $beamsClient->generateToken($publisherUserId);
//     }catch(Exception $e) {
//         return response(['Server Error. Faild to generate beams token.'], 500);
//     }

//     return response()->json($beamsToken);
// })->name('user.pusher.beams.auth');
