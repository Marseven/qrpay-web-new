<?php

namespace App\Http\Controllers\User;

use App\Constants\PaymentGatewayConst;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaymentGateway as PaymentGatewayHelper;
use App\Models\Admin\PaymentGateway;
use App\Http\Helpers\Response;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\TemporaryData;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use App\Models\UserWallet;
use Exception;
use Illuminate\Support\Facades\Session;
use App\Traits\PaymentGateway\Stripe;
use App\Traits\PaymentGateway\Manual;
use App\Models\Admin\BasicSettings;
use App\Traits\PaymentGateway\FlutterwaveTrait;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

class AddMoneyController extends Controller
{
    use Stripe, Manual, FlutterwaveTrait;


    public function index()
    {

        $page_title = "Add Money";
        $user_wallets = UserWallet::auth()->get();
        $user_currencies = Currency::whereIn('id', $user_wallets->pluck('id')->toArray())->get();

        $payment_gateways_currencies = PaymentGatewayCurrency::whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
            $gateway->where('status', 1);
        })->get();
        $transactions = Transaction::auth()->addMoney()->latest()->take(10)->get();
        return view('user.sections.add-money.index', compact("page_title", "transactions", "payment_gateways_currencies"));
    }



    public function submit(Request $request)
    {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        if ($basic_setting->kyc_verification) {
            if ($user->kyc_verified == 0) {
                return redirect()->route('user.profile.index')->with(['error' => ['Please submit kyc information']]);
            } elseif ($user->kyc_verified == 2) {
                return redirect()->route('user.profile.index')->with(['error' => ['Please wait before admin approved your kyc information']]);
            } elseif ($user->kyc_verified == 3) {
                return redirect()->route('user.profile.index')->with(['error' => ['Admin rejected your kyc information, Please re-submit again']]);
            }
        }
        try {
            $instance = PaymentGatewayHelper::init($request->all())->gateway()->render();
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        //dd($instance);
        return $instance;
    }

    public function success(Request $request, $gateway)
    {
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";
        $checkTempData = TemporaryData::where("type", $gateway)->where("identifier", $token)->first();
        if (!$checkTempData) return redirect()->route('user.add.money.index')->with(['error' => ['Transaction faild. Record didn\'t saved properly. Please try again.']]);
        $checkTempData = $checkTempData->toArray();

        try {
            PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive();
        } catch (Exception $e) {

            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->route("user.add.money.index")->with(['success' => ['Successfully added money']]);
    }

    public function cancel(Request $request, $gateway)
    {
        $token = session()->get('identifier');
        if ($token) {
            TemporaryData::where("identifier", $token)->delete();
        }

        return redirect()->route('user.add.money.index');
    }

    public function payment($gateway)
    {
        $page_title = "Stripe Payment";
        $tempData = Session::get('identifier');
        $hasData = TemporaryData::where('identifier', $tempData)->where('type', $gateway)->first();
        if (!$hasData) {
            return redirect()->route('user.add.money.index');
        }
        return view('user.sections.add-money.automatic.' . $gateway, compact("page_title", "hasData"));
    }
    public function manualPayment()
    {
        $tempData = Session::get('identifier');
        $hasData = TemporaryData::where('identifier', $tempData)->first();
        $gateway = PaymentGateway::manual()->where('slug', PaymentGatewayConst::add_money_slug())->where('id', $hasData->data->gateway)->first();
        $page_title = "Manual Payment" . ' ( ' . $gateway->name . ' )';
        if (!$hasData) {
            return redirect()->route('user.add.money.index');
        }
        return view('user.sections.add-money.manual.payment_confirmation', compact("page_title", "hasData", 'gateway'));
    }
    public function flutterwaveCallback()
    {

        $status = request()->status;


        //if payment is successful
        if ($status ==  'successful') {

            $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data = Flutterwave::verifyTransaction($transactionID);

            $requestData = request()->tx_ref;
            $token = $requestData;

            $checkTempData = TemporaryData::where("type", 'flutterwave')->where("identifier", $token)->first();

            if (!$checkTempData) return redirect()->route('user.add.money.index')->with(['error' => ['Transaction faild. Record didn\'t saved properly. Please try again.']]);

            $checkTempData = $checkTempData->toArray();

            try {
                PaymentGatewayHelper::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('flutterWave');
            } catch (Exception $e) {
                return back()->with(['error' => [$e->getMessage()]]);
            }
            return redirect()->route("user.add.money.index")->with(['success' => ['Successfully added money']]);
        } elseif ($status ==  'cancelled') {
            return redirect()->route('user.add.money.index')->with(['error' => ['Add money cancelled']]);
        } else {
            return redirect()->route('user.add.money.index')->with(['error' => ['Transaction failed']]);
        }
    }
}
