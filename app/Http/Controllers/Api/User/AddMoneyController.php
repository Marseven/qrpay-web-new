<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Admin\PaymentGatewayCurrencyController;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\PaymentGatewayApi;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\TemporaryData;
use App\Models\Transaction;
use App\Models\UserWallet;
use App\Traits\PaymentGateway\Ebilling;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\PaymentGateway\Stripe;
use App\Traits\PaymentGateway\Manual;
use Illuminate\Support\Facades\Http;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

class AddMoneyController extends Controller
{
    use Ebilling, Stripe, Manual;
    public function addMoneyInformation()
    {
        $user = auth()->user();
        $userWallet = UserWallet::where('user_id', $user->id)->get()->map(function ($data) {
            return [
                'balance' => getAmount($data->balance, 2),
                'currency' => get_default_currency_code(),
            ];
        })->first();
        $transactions = Transaction::auth()->addMoney()->latest()->take(5)->get()->map(function ($item) {
            $statusInfo = [
                "success" =>      1,
                "pending" =>      2,
                "rejected" =>     3,
            ];
            return [
                'id' => $item->id,
                'trx' => $item->trx_id,
                'gateway_name' => $item->currency->name,
                'transaction_type' => $item->type,
                'request_amount' => getAmount($item->request_amount, 4) . ' ' . get_default_currency_code(),
                'payable' => getAmount($item->payable, 4) . ' ' . $item->user_wallet->currency->code,
                'exchange_rate' => '1 ' . get_default_currency_code() . ' = ' . getAmount($item->currency->rate, 4) . ' ' . $item->currency->currency_code,
                'total_charge' => getAmount($item->charge->total_charge, 4) . ' ' . $item->user_wallet->currency->code,
                'current_balance' => getAmount($item->available_balance, 4) . ' ' . get_default_currency_code(),
                'status' => $item->stringStatus->value,
                'date_time' => $item->created_at,
                'status_info' => (object)$statusInfo,
                'rejection_reason' => $item->reject_reason ?? "",

            ];
        });


        $gateways = PaymentGateway::where('status', 1)->where('slug', PaymentGatewayConst::add_money_slug())->get()->map(function ($gateway) {
            $currencies = PaymentGatewayCurrency::where('payment_gateway_id', $gateway->id)->get()->map(function ($data) {
                return [
                    'id' => $data->id,
                    'payment_gateway_id' => $data->payment_gateway_id,
                    'type' => $data->gateway->type,
                    'name' => $data->name,
                    'alias' => $data->alias,
                    'currency_code' => $data->currency_code,
                    'currency_symbol' => $data->currency_symbol,
                    'image' => $data->image,
                    'min_limit' => getAmount($data->min_limit, 4),
                    'max_limit' => getAmount($data->max_limit, 4),
                    'percent_charge' => getAmount($data->percent_charge, 4),
                    'fixed_charge' => getAmount($data->fixed_charge, 4),
                    'rate' => getAmount($data->rate, 4),
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ];
            });
            return [
                'id' => $gateway->id,
                'image' => $gateway->image,
                'slug' => $gateway->slug,
                'code' => $gateway->code,
                'type' => $gateway->type,
                'alias' => $gateway->alias,
                'supported_currencies' => $gateway->supported_currencies,
                'status' => $gateway->status,
                'currencies' => $currencies

            ];
        });
        $data = [
            'base_curr'    => get_default_currency_code(),
            'base_curr_rate' => get_default_currency_rate(),
            'default_image'    => "public/backend/images/default/default.webp",
            "image_path"  =>  "public/backend/images/payment-gateways",
            'userWallet'   =>   (object)$userWallet,
            'gateways'   => $gateways,
            'transactionss'   =>   $transactions,
        ];
        $message =  ['success' => ['Add Money Information!']];
        return Helpers::success($data, $message);
    }

    public function submitData(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'currency'  => "required",
            'amount'        => "required|numeric",
        ]);
        if ($validator->fails()) {
            $error =  ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        if ($basic_setting->kyc_verification) {
            if ($user->kyc_verified == 0) {
                $error = ['error' => ['Please submit kyc information!']];
                return Helpers::error($error);
            } elseif ($user->kyc_verified == 2) {
                $error = ['error' => ['Please wait before admin approved your kyc information']];
                return Helpers::error($error);
            } elseif ($user->kyc_verified == 3) {
                $error = ['error' => ['Admin rejected your kyc information, Please re-submit again']];
                return Helpers::error($error);
            }
        }
        $alias = $request->currency;
        $amount = $request->amount;
        $payment_gateways_currencies = PaymentGatewayCurrency::where('alias', $alias)->whereHas('gateway', function ($gateway) {
            $gateway->where('slug', PaymentGatewayConst::add_money_slug());
            $gateway->where('status', 1);
        })->first();
        if (!$payment_gateways_currencies) {
            $error = ['error' => ['Gateway Information is not available. Please provide payment gateway currency alias']];
            return Helpers::error($error);
        }
        $defualt_currency = Currency::default();

        $user_wallet = UserWallet::auth()->where('currency_id', $defualt_currency->id)->first();

        if (!$user_wallet) {
            $error = ['error' => ['User wallet not found!']];
            return Helpers::error($error);
        }
        if ($amount < ($payment_gateways_currencies->min_limit / $payment_gateways_currencies->rate) || $amount > ($payment_gateways_currencies->max_limit / $payment_gateways_currencies->rate)) {
            $error = ['error' => ['Please follow the transaction limit']];
            return Helpers::error($error);
        }
        try {
            $instance = PaymentGatewayApi::init($request->all())->gateway()->api()->get();

            $trx = $instance['response']['id'] ?? $instance['response']['trx'];
            $temData = TemporaryData::where('identifier', $trx)->first();
            if (!$temData) {
                $error = ['error' => ["Invalid Request"]];
                return Helpers::error($error);
            }
            $payment_gateway_currency = PaymentGatewayCurrency::where('id', $temData->data->currency)->first();
            $payment_gateway = PaymentGateway::where('id', $temData->data->gateway)->first();
            if ($payment_gateway->type == "AUTOMATIC") {
                if ($temData->type == PaymentGatewayConst::EBILLING) {

                    $payment_informations = [
                        'trx' =>  $temData->identifier,
                        'bill_id' =>  $instance['response']['bill_id'],
                        'gateway_currency_name' =>  $payment_gateway_currency->name,
                        'request_amount' => getAmount($temData->data->amount->requested_amount, 4) . ' ' . $temData->data->amount->default_currency,
                        'exchange_rate' => "1" . ' ' . $temData->data->amount->default_currency . ' = ' . getAmount($temData->data->amount->sender_cur_rate) . ' ' . $temData->data->amount->sender_cur_code,
                        'total_charge' => getAmount($temData->data->amount->total_charge, 2) . ' ' . $temData->data->amount->sender_cur_code,
                        'will_get' => getAmount($temData->data->amount->will_get, 2) . ' ' . $temData->data->amount->default_currency,
                        'payable_amount' =>  getAmount($temData->data->amount->total_amount, 2) . ' ' . $temData->data->amount->sender_cur_code,
                    ];
                    $data = [
                        'gategay_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_informations' => $payment_informations,
                        'url' => @$temData->data->response->links,
                        'method' => "get",
                    ];
                    $message =  ['success' => ['Add Money Inserted Successfully']];
                    return Helpers::success($data, $message);
                } else if ($temData->type == PaymentGatewayConst::STRIPE) {
                    $card = [
                        [
                            'field_name' => "name",
                            'label_name' => "Name",
                        ],
                        [
                            'field_name' => "cardNumber",
                            'label_name' => "Card Number",
                        ],
                        [
                            'field_name' => "cardExpiry",
                            'label_name' => "Expire Date",
                        ],
                        [
                            'field_name' => "cardCVC",
                            'label_name' => "CVC Code",
                        ],
                    ];
                    $card2 = (array) $card;
                    $payment_informations = [
                        'trx' =>  $temData->identifier,
                        'gateway_currency_name' =>  $payment_gateway_currency->name,
                        'request_amount' => getAmount($temData->data->amount->requested_amount, 2) . ' ' . $temData->data->amount->default_currency,
                        'exchange_rate' => "1" . ' ' . $temData->data->amount->default_currency . ' = ' . getAmount($temData->data->amount->sender_cur_rate) . ' ' . $temData->data->amount->sender_cur_code,
                        'total_charge' => getAmount($temData->data->amount->total_charge, 2) . ' ' . $temData->data->amount->sender_cur_code,
                        'will_get' => getAmount($temData->data->amount->will_get, 2) . ' ' . $temData->data->amount->default_currency,
                        'payable_amount' =>  getAmount($temData->data->amount->total_amount, 2) . ' ' . $temData->data->amount->sender_cur_code,
                    ];

                    $data = [
                        'gategay_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'input_fields' => $card2,
                        'payment_informations' => $payment_informations,
                        'url' => route('api.stripe.payment.confirmed'),
                        'method' => "post",
                    ];
                    $message =  ['success' => ['Add Money Inserted Successfully']];
                    return Helpers::success($data, $message);
                } else if ($temData->type == PaymentGatewayConst::PAYPAL) {

                    $payment_informations = [
                        'trx' =>  $temData->identifier,
                        'gateway_currency_name' =>  $payment_gateway_currency->name,
                        'request_amount' => getAmount($temData->data->amount->requested_amount, 4) . ' ' . $temData->data->amount->default_currency,
                        'exchange_rate' => "1" . ' ' . $temData->data->amount->default_currency . ' = ' . getAmount($temData->data->amount->sender_cur_rate) . ' ' . $temData->data->amount->sender_cur_code,
                        'total_charge' => getAmount($temData->data->amount->total_charge, 2) . ' ' . $temData->data->amount->sender_cur_code,
                        'will_get' => getAmount($temData->data->amount->will_get, 2) . ' ' . $temData->data->amount->default_currency,
                        'payable_amount' =>  getAmount($temData->data->amount->total_amount, 2) . ' ' . $temData->data->amount->sender_cur_code,
                    ];
                    $data = [
                        'gategay_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_informations' => $payment_informations,
                        'url' => @$temData->data->response->links,
                        'method' => "get",
                    ];
                    $message =  ['success' => ['Add Money Inserted Successfully']];
                    return Helpers::success($data, $message);
                } else if ($temData->type == PaymentGatewayConst::FLUTTER_WAVE) {
                    $payment_informations = [
                        'trx' =>  $temData->identifier,
                        'gateway_currency_name' =>  $payment_gateway_currency->name,
                        'request_amount' => getAmount($temData->data->amount->requested_amount, 4) . ' ' . $temData->data->amount->default_currency,
                        'exchange_rate' => "1" . ' ' . $temData->data->amount->default_currency . ' = ' . getAmount($temData->data->amount->sender_cur_rate, 4) . ' ' . $temData->data->amount->sender_cur_code,
                        'total_charge' => getAmount($temData->data->amount->total_charge, 4) . ' ' . $temData->data->amount->sender_cur_code,
                        'will_get' => getAmount($temData->data->amount->will_get, 4) . ' ' . $temData->data->amount->default_currency,
                        'payable_amount' =>  getAmount($temData->data->amount->total_amount, 4) . ' ' . $temData->data->amount->sender_cur_code,
                    ];
                    $data = [
                        'gateway_type' => $payment_gateway->type,
                        'gateway_currency_name' => $payment_gateway_currency->name,
                        'alias' => $payment_gateway_currency->alias,
                        'identify' => $temData->type,
                        'payment_informations' => $payment_informations,
                        'url' => @$temData->data->response->link,
                        'method' => "get",
                    ];
                    $message =  ['success' => ['Add Money Inserted Successfully']];
                    return Helpers::success($data, $message);
                }
            } elseif ($payment_gateway->type == "MANUAL") {

                $payment_informations = [
                    'trx' =>  $temData->identifier,
                    'gateway_currency_name' =>  $payment_gateway_currency->name,
                    'request_amount' => getAmount($temData->data->amount->requested_amount, 2) . ' ' . $temData->data->amount->default_currency,
                    'exchange_rate' => "1" . ' ' . $temData->data->amount->default_currency . ' = ' . getAmount($temData->data->amount->sender_cur_rate) . ' ' . $temData->data->amount->sender_cur_code,
                    'total_charge' => getAmount($temData->data->amount->total_charge, 2) . ' ' . $temData->data->amount->sender_cur_code,
                    'will_get' => getAmount($temData->data->amount->will_get, 2) . ' ' . $temData->data->amount->default_currency,
                    'payable_amount' =>  getAmount($temData->data->amount->total_amount, 2) . ' ' . $temData->data->amount->sender_cur_code,
                ];
                $data = [
                    'gategay_type' => $payment_gateway->type,
                    'gateway_currency_name' => $payment_gateway_currency->name,
                    'alias' => $payment_gateway_currency->alias,
                    'identify' => $temData->type,
                    'details' => $payment_gateway->desc ?? null,
                    'input_fields' => $payment_gateway->input_fields ?? null,
                    'payment_informations' => $payment_informations,
                    'url' => route('api.manual.payment.confirmed'),
                    'method' => "post",
                ];
                $message =  ['success' => ['Add Money Inserted Successfully']];
                return Helpers::success($data, $message);
            } else {
                $error = ['error' => ["Something is wrong"]];
                return Helpers::error($error);
            }
        } catch (Exception $e) {
            $error = ['error' => [$e->getMessage()]];
            return Helpers::error($error);
        }
        // return $instance;
    }

    public function success(Request $request, $gateway)
    {
        $requestData = $request->all();
        $token = $requestData['token'] ?? "";
        $checkTempData = TemporaryData::where("type", $gateway)->where("identifier", $token)->first();
        if (!$checkTempData) {
            $message = ['error' => ["Transaction failed. Record didn\'t saved properly. Please try again."]];
            return Helpers::error($message);
        }

        $checkTempData = $checkTempData->toArray();
        try {
            PaymentGatewayApi::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive();
        } catch (Exception $e) {
            $message = ['error' => [$e->getMessage()]];
            return Helpers::error($message);
        }
        $message = ['success' => ["Payment successful, please go back your app"]];
        return Helpers::onlysuccess($message);
    }

    public function cancel(Request $request, $gateway)
    {
        $message = ['error' => ["Something is worng"]];
        return Helpers::error($message);
    }

    public function flutterwaveCallback()
    {

        $status = request()->status;

        if ($status ==  'successful') {

            $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data = Flutterwave::verifyTransaction($transactionID);

            $requestData = request()->tx_ref;

            $token = $requestData;

            $checkTempData = TemporaryData::where("type", 'flutterwave')->where("identifier", $token)->first();

            $message = ['error' => ['Transaction faild. Record didn\'t saved properly. Please try again.']];

            if (!$checkTempData) return Helpers::error($message);

            $checkTempData = $checkTempData->toArray();
            try {
                PaymentGatewayApi::init($checkTempData)->type(PaymentGatewayConst::TYPEADDMONEY)->responseReceive('flutterWave');
            } catch (Exception $e) {
                $message = ['error' => [$e->getMessage()]];
                Helpers::error($message);
            }
            $message = ['success' => ["Payment successful"]];
            return Helpers::onlysuccess($message);
        } elseif ($status ==  'cancelled') {
            $message = ['error' => ['Payment Cancelled']];
            Helpers::error($message);
        } else {
            $message = ['error' => ['Payment Failed']];
            Helpers::error($message);
        }
    }

    public function ebillingPush(Request $request)
    {
        $gateway = PaymentGateway::addMoney()->automatic()->gateway('ebilling')->first();
        $client_username_sample = ['username', 'user_name', 'Username', 'primary key'];
        $client_sharedkey_sample = ['shared_key', 'Shared Key', 'shared', 'shared key', 'shared id'];
        $username = '';
        $outer_break = false;
        foreach ($client_username_sample as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $item;
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                if ($label == $modify_item) {
                    $username = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $sharedkey = '';
        $outer_break = false;
        foreach ($client_sharedkey_sample as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $item;
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                if ($label == $modify_item) {
                    $sharedkey = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $mode = $gateway->env;

        $ebilling_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if (array_key_exists($mode, $ebilling_register_mode)) {
            $mode = $ebilling_register_mode[$mode];
        } else {
            $mode = "sandbox";
        }

        $auth = $username . ':' . $sharedkey;

        $base64 = base64_encode($auth);

        $response = Http::withHeaders([
            "Authorization" => "Basic " . $base64
        ])->post(env('SERVER_URL') . 'e_bills/' . $request->all()['bill_id'] . '/ussd_push', [
            "payment_system_name" => $request->all()['payment_system_name'],
            "payer_msisdn" => $request->all()['payer_msisdn'],
        ]);

        $response = json_decode($response->body());

        if ($response) {
            if ($response->message == "Accepted") {
                $message = ['success' => [$response->message]];
                return Helpers::onlysuccess($message);
            } else {
                $message = ['error' => [$response->message]];
                return Helpers::error($message);
            }
        } else {
            $message = ['error' => ['Failed']];
            return Helpers::error($message);
        }
    }

    public function ebillingCheck(Request $request)
    {
        $trx = Transaction::where('trx_id', $request->all()['trx'])->first();
        if ($trx &&  $trx->status == PaymentGatewayConst::STATUSSUCCESS) {
            $message = ['success' => ["Payment successful"]];
            return Helpers::onlysuccess($message);
        } else {
            $message = ['success' => ['Failed']];
            return Helpers::onlysuccess($message);
        }
    }
}
