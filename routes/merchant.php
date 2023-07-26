<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GlobalController;
use App\Providers\Admin\BasicSettingsProvider;
use App\Http\Controllers\User\WalletController;
use Pusher\PushNotifications\PushNotifications;
use App\Http\Controllers\Merchant\DashboardController;
use App\Http\Controllers\Merchant\MoneyOutController;
use App\Http\Controllers\Merchant\ProfileController;
use App\Http\Controllers\Merchant\ReceiveMoneyController;
use App\Http\Controllers\Merchant\SecurityController as MerchantSecurityController;
use App\Http\Controllers\Merchant\SupportTicketController;
use App\Http\Controllers\Merchant\TransactionController;

Route::prefix("merchant")->name("merchant.")->group(function(){
    Route::controller(DashboardController::class)->group(function(){
        Route::get('dashboard','index')->name('dashboard');
        Route::post('logout','logout')->name('logout');
    });

    //profile
    Route::controller(ProfileController::class)->prefix("profile")->name("profile.")->group(function(){
        Route::get('/','index')->name('index');
        Route::put('password/update','passwordUpdate')->name('password.update');
        Route::put('update','update')->name('update');
        Route::delete('delete/account','deleteAccount')->name('delete.account');
    });

     //Receive Money
     Route::controller(ReceiveMoneyController::class)->prefix('receive-money')->name('receive.money.')->group(function(){
        Route::get('/','index')->name('index');
    });
    Route::controller(WalletController::class)->prefix("wallets")->name("wallets.")->group(function(){
        Route::get("/","index")->name("index");
        Route::post("balance","balance")->name("balance");
    });

    //money out
    Route::controller(MoneyOutController::class)->prefix('withdraw')->name('withdraw.')->group(function(){
        Route::get('/','index')->name('index');
        Route::post('insert','paymentInsert')->name('insert');
        Route::get('preview','preview')->name('preview');
        Route::post('confirm','confirmMoneyOut')->name('confirm');
        //check bank validation
        Route::post('check/flutterwave/bank','checkBanks')->name('check.flutterwave.bank');
        //automatic withdraw confirmed
        Route::post('automatic/confirmed','confirmMoneyOutAutomatic')->name('confirm.automatic');
    });
    //transactions
    Route::controller(TransactionController::class)->prefix("transactions")->name("transactions.")->group(function(){
        Route::get('/{slug?}','index')->name('index')->whereIn('slug',['add-money','withdraw','transfer-money','money-exchange','bill-pay','mobile-topup','virtual-card','remittance']);
        // Route::get('log/{slug?}','log')->name('log')->whereIn('slug',['add-money','money-out','transfer-money']);
        Route::post('search','search')->name('search');
    });
    //google-2fa
    Route::controller(MerchantSecurityController::class)->prefix("security")->name('security.')->group(function(){
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
Route::get('merchant/pusher/beams-auth', function (Request $request) {
    if(Auth::check() == false) {
        return response(['Inconsistent request'], 401);
    }
    $userID = userGuard()['user']->id;

    $basic_settings = BasicSettingsProvider::get();
    if(!$basic_settings) {
        return response('Basic setting not found!', 404);
    }

    $notification_config = $basic_settings->push_notification_config;

    if(!$notification_config) {
        return response('Notification configuration not found!', 404);
    }

    $instance_id    = $notification_config->instance_id ?? null;
    $primary_key    = $notification_config->primary_key ?? null;
    if($instance_id == null || $primary_key == null) {
        return response('Sorry! You have to configure first to send push notification.', 404);
    }
    $beamsClient = new PushNotifications(
        array(
            "instanceId" => $notification_config->instance_id,
            "secretKey" => $notification_config->primary_key,
        )
    );
    $publisherUserId = "merchant-".$userID;
    try{
        $beamsToken = $beamsClient->generateToken($publisherUserId);
    }catch(Exception $e) {
        return response(['Server Error. Faild to generate beams token.'], 500);
    }

    return response()->json($beamsToken);
})->name('merchant.pusher.beams.auth');
