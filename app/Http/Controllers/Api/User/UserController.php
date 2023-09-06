<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use App\Models\VirtualCard;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Constants\GlobalConst;
use App\Models\VirtualCardApi;
use App\Http\Helpers\Api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Admin\BasicSettings;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Providers\Admin\BasicSettingsProvider;

class UserController extends Controller
{
    protected $api;
    public function __construct()
    {
        $cardApi = VirtualCardApi::first();
        $this->api =  $cardApi;
    }
    public function home()
    {
        $user = auth()->user();
        $totalAddMoney = Transaction::auth()->addMoney()->where('status', 1)->sum('request_amount');
        $totalReceiveRemittance = Transaction::auth()->remitance()->where('attribute', "RECEIVED")->sum('request_amount');
        $totalSendRemittance = Transaction::auth()->remitance()->where('attribute', "SEND")->sum('request_amount');
        $cardAmount = VirtualCard::where('user_id', auth()->user()->id)->sum('amount');
        $billPay = Transaction::auth()->billPay()->sum('request_amount');
        $ticketPay = Transaction::auth()->ticketPay()->sum('request_amount');
        $topUps = Transaction::auth()->mobileTopup()->sum('request_amount');
        $toatlTransactions = Transaction::auth()->where('status', 1)->sum('request_amount');

        $userWallet = UserWallet::where('user_id', $user->id)->get()->map(function ($data) {
            return [
                'balance' => getAmount($data->balance, 2),
                'currency' => get_default_currency_code(),
            ];
        })->first();
        $transactions = Transaction::auth()->latest()->take(5)->get()->map(function ($item) {

            $basic_settings = BasicSettings::first();
            $statusInfo = [
                "success" =>      1,
                "pending" =>      2,
                "rejected" =>     3,
            ];
            if ($item->type == payment_gateway_const()::TYPEADDMONEY) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'gateway_name' => $item->currency->name,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . $item->user_wallet->currency->code,
                    'exchange_rate' => '1 ' . get_default_currency_code() . ' = ' . getAmount($item->currency->rate, 2) . ' ' . $item->currency->currency_code,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . $item->user_wallet->currency->code,
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,
                    'rejection_reason' => $item->reject_reason ?? "",

                ];
            } elseif ($item->type == payment_gateway_const()::BILLPAY) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . get_default_currency_code(),
                    'bill_type' => $item->details->bill_type_name,
                    'bill_number' => $item->details->bill_number,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,
                    'rejection_reason' => $item->reject_reason ?? "",

                ];
            } elseif ($item->type == payment_gateway_const()::TICKETPAY) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . get_default_currency_code(),
                    'bill_type' => $item->details->ticket_type_name,
                    'bill_number' => $item->details->ticket_number,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,
                    'rejection_reason' => $item->reject_reason ?? "",

                ];
            } elseif ($item->type == payment_gateway_const()::MOBILETOPUP) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . get_default_currency_code(),
                    'topup_type' => $item->details->topup_type_name,
                    'mobile_number' => $item->details->mobile_number,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,
                    'rejection_reason' => $item->reject_reason ?? "",

                ];
            } elseif ($item->type == payment_gateway_const()::TYPEMONEYOUT) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'gateway_name' => $item->currency->gateway->name,
                    'gateway_currency_name' => $item->currency->name,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . $item->user_wallet->currency->code,
                    'exchange_rate' => '1 ' . get_default_currency_code() . ' = ' . getAmount($item->currency->rate, 2) . ' ' . $item->currency->currency_code,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . $item->user_wallet->currency->code,
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,
                    'rejection_reason' => $item->reject_reason ?? "",

                ];
            } elseif ($item->type == payment_gateway_const()::SENDREMITTANCE) {
                if (@$item->details->remitance_type == "wallet-to-wallet-transfer") {
                    $transactionType = @$basic_settings->site_name . " Wallet";
                } else {
                    $transactionType = ucwords(str_replace('-', ' ', @$item->details->remitance_type));
                }
                if ($item->attribute == payment_gateway_const()::SEND) {
                    if (@$item->details->remitance_type == Str::slug(GlobalConst::TRX_WALLET_TO_WALLET_TRANSFER)) {
                        return [
                            'id' => @$item->id,
                            'type' => $item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Send Remitance to @" . $item->details->receiver->firstname . ' ' . @$item->details->receiver->lastname . " (" . @$item->details->receiver->mobile_code . @$item->details->receiver->mobile . ")",
                            'request_amount' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                            'total_charge' => getAmount(@$item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                            'exchange_rate' => "1" . ' ' . get_default_currency_code() . ' = ' . get_amount($item->details->to_country->rate, $item->details->to_country->code),
                            'payable' => getAmount(@$item->payable, 2) . ' ' . get_default_currency_code(),
                            'sending_country' => @$item->details->form_country,
                            'receiving_country' => @$item->details->to_country->country,
                            'receipient_name' => @$item->details->receiver->firstname . ' ' . @$item->details->receiver->lastname,
                            'remittance_type' => Str::slug(GlobalConst::TRX_WALLET_TO_WALLET_TRANSFER),
                            'remittance_type_name' => $transactionType,
                            'receipient_get' =>  get_amount(@$item->details->recipient_amount, $item->details->to_country->code),
                            'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                            'status' => @$item->stringStatus->value,
                            'date_time' => @$item->created_at,
                            'status_info' => (object)@$statusInfo,
                            'rejection_reason' => $item->reject_reason ?? "",
                        ];
                    } elseif (@$item->details->remitance_type == Str::slug(GlobalConst::TRX_BANK_TRANSFER)) {
                        return [
                            'id' => @$item->id,
                            'type' => $item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Send Remitance to @" . $item->details->receiver->firstname . ' ' . @$item->details->receiver->lastname . " (" . @$item->details->receiver->mobile_code . @$item->details->receiver->mobile . ")",
                            'request_amount' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                            'total_charge' => getAmount(@$item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                            'exchange_rate' => "1" . ' ' . get_default_currency_code() . ' = ' . get_amount($item->details->to_country->rate, $item->details->to_country->code),
                            'payable' => getAmount(@$item->payable, 2) . ' ' . get_default_currency_code(),
                            'sending_country' => @$item->details->form_country,
                            'receiving_country' => @$item->details->to_country->country,
                            'receipient_name' => @$item->details->receiver->firstname . ' ' . @$item->details->receiver->lastname,
                            'remittance_type' => Str::slug(GlobalConst::TRX_BANK_TRANSFER),
                            'remittance_type_name' => $transactionType,
                            'receipient_get' =>  get_amount(@$item->details->recipient_amount, $item->details->to_country->code),
                            'bank_name' => ucwords(str_replace('-', ' ', @$item->details->receiver->alias)),
                            'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                            'status' => @$item->stringStatus->value,
                            'date_time' => @$item->created_at,
                            'status_info' => (object)@$statusInfo,
                            'rejection_reason' => $item->reject_reason ?? "",
                        ];
                    } elseif (@$item->details->remitance_type == Str::slug(GlobalConst::TRX_CASH_PICKUP)) {
                        return [
                            'id' => @$item->id,
                            'type' => $item->attribute,
                            'trx' => @$item->trx_id,
                            'transaction_type' => $item->type,
                            'transaction_heading' => "Send Remitance to @" . $item->details->receiver->firstname . ' ' . @$item->details->receiver->lastname . " (" . @$item->details->receiver->mobile_code . @$item->details->receiver->mobile . ")",
                            'request_amount' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                            'total_charge' => getAmount(@$item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                            'exchange_rate' => "1" . ' ' . get_default_currency_code() . ' = ' . get_amount($item->details->to_country->rate, $item->details->to_country->code),
                            'payable' => getAmount(@$item->payable, 2) . ' ' . get_default_currency_code(),
                            'sending_country' => @$item->details->form_country,
                            'receiving_country' => @$item->details->to_country->country,
                            'receipient_name' => @$item->details->receiver->firstname . ' ' . @$item->details->receiver->lastname,
                            'remittance_type' => Str::slug(GlobalConst::TRX_CASH_PICKUP),
                            'remittance_type_name' => $transactionType,
                            'receipient_get' =>  get_amount(@$item->details->recipient_amount, $item->details->to_country->code),
                            'pickup_point' => ucwords(str_replace('-', ' ', @$item->details->receiver->alias)),
                            'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                            'status' => @$item->stringStatus->value,
                            'date_time' => @$item->created_at,
                            'status_info' => (object)@$statusInfo,
                            'rejection_reason' => $item->reject_reason ?? "",
                        ];
                    }
                } elseif ($item->attribute == payment_gateway_const()::RECEIVED) {
                    return [
                        'id' => @$item->id,
                        'type' => $item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Received Remitance from @" . @$item->details->sender->fullname . " (" . @$item->details->sender->full_mobile . ")",
                        'request_amount' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                        'sending_country' => @$item->details->form_country,
                        'receiving_country' => @$item->details->to_country->country,
                        'remittance_type' => Str::slug(GlobalConst::TRX_CASH_PICKUP),
                        'remittance_type_name' => $transactionType,
                        'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                        'status' => @$item->stringStatus->value,
                        'date_time' => @$item->created_at,
                        'status_info' => (object)@$statusInfo,
                        'rejection_reason' => $item->reject_reason ?? "",
                    ];
                }
            } elseif ($item->type == payment_gateway_const()::TYPETRANSFERMONEY) {
                if ($item->attribute == payment_gateway_const()::SEND) {
                    return [
                        'id' => @$item->id,
                        'type' => $item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Send Money to @" . @$item->details->receiver->username . " (" . @$item->details->receiver->full_mobile . ")",
                        'request_amount' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                        'total_charge' => getAmount(@$item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                        'payable' => getAmount(@$item->payable, 2) . ' ' . get_default_currency_code(),
                        'recipient_received' => getAmount(@$item->details->recipient_amount, 2) . ' ' . get_default_currency_code(),
                        'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                        'status' => @$item->stringStatus->value,
                        'date_time' => @$item->created_at,
                        'status_info' => (object)@$statusInfo,
                    ];
                } elseif ($item->attribute == payment_gateway_const()::RECEIVED) {
                    return [
                        'id' => @$item->id,
                        'type' => $item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Received Money from @" . @$item->details->sender->username . " (" . @$item->details->sender->full_mobile . ")",
                        'recipient_received' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                        'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                        'status' => @$item->stringStatus->value,
                        'date_time' => @$item->created_at,
                        'status_info' => (object)@$statusInfo,
                    ];
                }
            } elseif ($item->type == payment_gateway_const()::VIRTUALCARD) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => "Virtual Card" . '(' . @$item->remark . ')',
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . get_default_currency_code(),
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                    'card_amount' => getAmount(@$item->details->card_info->amount, 2) . ' ' . get_default_currency_code(),
                    'card_number' => $item->details->card_info->card_pan,
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,

                ];
            } elseif ($item->type == payment_gateway_const()::TYPEMAKEPAYMENT) {

                if ($item->attribute == payment_gateway_const()::SEND) {
                    return [
                        'id' => @$item->id,
                        'type' => $item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Make Payment to @" . @$item->details->receiver->fullname . " (" . @$item->details->receiver->full_mobile . ")",
                        'request_amount' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                        'total_charge' => getAmount(@$item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                        'payable' => getAmount(@$item->payable, 2) . ' ' . get_default_currency_code(),
                        'recipient_received' => getAmount(@$item->details->recipient_amount, 2) . ' ' . get_default_currency_code(),
                        'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                        'status' => @$item->stringStatus->value,
                        'date_time' => @$item->created_at,
                        'status_info' => (object)@$statusInfo,
                    ];
                } elseif ($item->attribute == payment_gateway_const()::RECEIVED) {
                    return [
                        'id' => @$item->id,
                        'type' => $item->attribute,
                        'trx' => @$item->trx_id,
                        'transaction_type' => $item->type,
                        'transaction_heading' => "Received Money from @" . @$item->details->sender->fullname . " (" . @$item->details->sender->full_mobile . ")",
                        'recipient_received' => getAmount(@$item->request_amount, 2) . ' ' . get_default_currency_code(),
                        'current_balance' => getAmount(@$item->available_balance, 2) . ' ' . get_default_currency_code(),
                        'status' => @$item->stringStatus->value,
                        'date_time' => @$item->created_at,
                        'status_info' => (object)@$statusInfo,
                    ];
                }
            } elseif ($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transaction_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'receive_amount' => getAmount($item->payable, 2) . ' ' . get_default_currency_code(),
                    'exchange_rate' => '1 ' . get_default_currency_code() . ' = ' . getAmount($item->user_wallet->currency->rate, 2) . ' ' . $item->user_wallet->currency->code,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                    'remark' => $item->remark,
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,

                ];
            }
        });

        $data = [
            'base_curr'    => get_default_currency_code(),
            'userWallet'   =>   (object)$userWallet,
            'default_image'    => "public/backend/images/default/profile-default.webp",
            "image_path"  =>  "public/frontend/user",
            'user'         =>   $user,
            'totalAddMoney'   =>  getAmount($totalAddMoney, 2) . ' ' . get_default_currency_code(),
            'totalReceiveRemittance'   =>  getAmount($totalReceiveRemittance, 2) . ' ' . get_default_currency_code(),
            'totalSendRemittance'   =>  getAmount($totalSendRemittance, 2) . ' ' . get_default_currency_code(),
            'cardAmount'   =>  getAmount($cardAmount, 2) . ' ' . get_default_currency_code(),
            'billPay'   =>  getAmount($billPay, 2) . ' ' . get_default_currency_code(),
            'topUps'   =>  getAmount($topUps, 2) . ' ' . get_default_currency_code(),
            'toatlTransactions'   =>  getAmount($toatlTransactions, 2) . ' ' . get_default_currency_code(),
            'transactionss'   =>   $transactions,
        ];
        $message =  ['success' => ['User Dashboard']];
        return Helpers::success($data, $message);
    }
    public function profile()
    {
        $user = auth()->user();
        $data = [
            'default_image'    => "public/backend/images/default/profile-default.webp",
            "image_path"  =>  "public/frontend/user",
            'user'         =>   $user,
        ];
        $message =  ['success' => ['User Profile']];
        return Helpers::success($data, $message);
    }
    public function profileUpdate(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'firstname'     => "required|string|max:60",
            'lastname'      => "required|string|max:60",
            'country'       => "required|string|max:50",
            'phone_code'    => "required|string|max:6",
            'phone'         => "required|string|max:11|unique:users,mobile," . $user->id,
            'state'         => "nullable|string|max:50",
            'city'          => "nullable|string|max:50",
            'zip_code'      => "nullable|numeric",
            'address'       => "nullable|string|max:250",
            'image'         => "nullable|image|mimes:jpg,png,svg,webp|max:10240",
        ]);
        if ($validator->fails()) {
            $error =  ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }
        $data = $request->all();
        $mobileCode = remove_speacial_char($data['phone_code']);
        $mobile = remove_speacial_char($data['phone']);

        $validated['firstname']      = $data['firstname'];
        $validated['lastname']      = $data['lastname'];
        $validated['mobile']        = $mobile;
        $validated['mobile_code']   = $mobileCode;
        $complete_phone             = $mobileCode . $mobile;

        $validated['full_mobile']   = $complete_phone;

        $validated['address']       = [
            'country'   => $data['country'] ?? "",
            'state'     => $data['state'] ?? "",
            'city'      => $data['city'] ?? "",
            'zip'       => $data['zip_code'] ?? "",
            'address'   => $data['address'] ?? "",
        ];


        if ($request->hasFile("image")) {
            if ($user->image == 'default.png') {
                $oldImage = null;
            } else {
                $oldImage = $user->image;
            }
            $image = upload_file($data['image'], 'user-profile', $oldImage);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']], 'user-profile');
            delete_file($image['dev_path']);
            $validated['image']     = $upload_image;
        }

        try {
            $user->update($validated);
        } catch (Exception $e) {
            $error = ['error' => ['Something went worng! Please try again']];
            return Helpers::error($error);
        }
        $message =  ['success' => ['Profile successfully updated!']];
        return Helpers::onlysuccess($message);
    }
    public function passwordUpdate(Request $request)
    {

        $basic_settings = BasicSettingsProvider::get();
        $passowrd_rule = "required|string|min:6|confirmed";
        if ($basic_settings->secure_password) {
            $passowrd_rule = ["required", Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(), "confirmed"];
        }
        $validator = Validator::make($request->all(), [
            'current_password'      => "required|string",
            'password'              => $passowrd_rule,
        ]);
        if ($validator->fails()) {
            $error =  ['error' => $validator->errors()->all()];
            return Helpers::validation($error);
        }
        if (!Hash::check($request->current_password, auth()->user()->password)) {
            $error = ['error' => ['Current password didn\'t match']];
            return Helpers::error($error);
        }

        try {
            auth()->user()->update([
                'password'  => Hash::make($request->password),
            ]);
        } catch (Exception $e) {
            $error = ['error' => ['Something went worng! Please try again']];
            return Helpers::error($error);
        }
        $message =  ['success' => ['Password successfully updated!']];
        return Helpers::onlysuccess($message);
    }
    public function deleteAccount(Request $request)
    {
        $user = auth()->user();
        $user->status = false;
        $user->email_verified = false;
        $user->sms_verified = false;
        $user->kyc_verified = false;
        $user->deleted_at = now();
        $user->save();

        try {
            $user->token()->revoke();
            $message =  ['success' => ['User deleted successfully']];
            return Helpers::onlysuccess($message);
        } catch (Exception $e) {
            $error = ['error' => ['Something went worng! Please try again']];
            return Helpers::error($error);
        }
    }

    public function transactions()
    {
        $transactions = Transaction::auth()->latest()->get()->map(function ($item) {
            $statusInfo = [
                "success" =>      1,
                "pending" =>      2,
                "rejected" =>     3,
            ];
            if ($item->type == payment_gateway_const()::TYPEADDMONEY) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'gateway_name' => $item->currency->name,
                    'transactin_type' => $item->type,
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . $item->user_wallet->currency->code,
                    'exchange_rate' => '1 ' . get_default_currency_code() . ' = ' . getAmount($item->currency->rate, 2) . ' ' . $item->currency->currency_code,
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . $item->user_wallet->currency->code,
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,
                    'rejection_reason' => $item->reject_reason ?? "",

                ];
            } elseif ($item->type == payment_gateway_const()::VIRTUALCARD) {
                return [
                    'id' => $item->id,
                    'trx' => $item->trx_id,
                    'transactin_type' => "Virtual Card" . '(' . @$item->remark . ')',
                    'request_amount' => getAmount($item->request_amount, 2) . ' ' . get_default_currency_code(),
                    'payable' => getAmount($item->payable, 2) . ' ' . get_default_currency_code(),
                    'total_charge' => getAmount($item->charge->total_charge, 2) . ' ' . get_default_currency_code(),
                    'card_amount' => getAmount(@$item->details->card_info->amount, 2) . ' ' . get_default_currency_code(),
                    'card_number' => $item->details->card_info->card_pan,
                    'current_balance' => getAmount($item->available_balance, 2) . ' ' . get_default_currency_code(),
                    'status' => $item->stringStatus->value,
                    'date_time' => $item->created_at,
                    'status_info' => (object)$statusInfo,

                ];
            }
        });
        $data = [
            'base_curr' => get_default_currency_code(),
            'transactions'   => (object)$transactions,
        ];
        $message =  ['success' => ['Fess & Charges']];
        return Helpers::success($data, $message);
    }
    public function notifications()
    {
        $user = auth()->user();
        $notifications = UserNotification::auth()->latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'title' => $item->message->title ?? "",
                'message' => $item->message->message ?? "",
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,

            ];
        });
        $data = [
            'notifications'  => $notifications
        ];
        $message =  ['success' => ['User Notifications']];
        return Helpers::success($data, $message);
    }
}
